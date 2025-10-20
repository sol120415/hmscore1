USE hmscore1;

-- EVENT VENUES TABLE
CREATE TABLE IF NOT EXISTS event_venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_name ENUM('Theater', 'Auditorium', 'Convention Center', 'Exhibition Hall') DEFAULT 'Theater',
    venue_address VARCHAR(255) NOT NULL,
    venue_capacity INT NOT NULL, -- Removed invalid CASE syntax
    venue_rate DECIMAL(10,2), -- Removed invalid AS/CASE syntax, made non-generated column
    venue_description TEXT,
    venue_status ENUM('Available', 'Booked', 'Maintenance') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default venue capacities and rates
INSERT INTO event_venues (venue_name, venue_address, venue_capacity, venue_rate, venue_description, venue_status)
SELECT 'Theater', '123 Theater St', 50, 5000.00, 'Theater venue', 'Available'
WHERE NOT EXISTS (SELECT 1 FROM event_venues WHERE venue_name = 'Theater')
UNION
SELECT 'Auditorium', '456 Auditorium Ave', 100, 8000.00, 'Auditorium venue', 'Available'
WHERE NOT EXISTS (SELECT 1 FROM event_venues WHERE venue_name = 'Auditorium')
UNION
SELECT 'Convention Center', '789 Convention Blvd', 200, 15000.00, 'Convention Center venue', 'Available'
WHERE NOT EXISTS (SELECT 1 FROM event_venues WHERE venue_name = 'Convention Center')
UNION
SELECT 'Exhibition Hall', '101 Exhibition Rd', 500, 25000.00, 'Exhibition Hall venue', 'Available'
WHERE NOT EXISTS (SELECT 1 FROM event_venues WHERE venue_name = 'Exhibition Hall');

SELECT 'Event venues table created successfully!' AS Status;

-- EVENT RESERVATIONS TABLE
CREATE TABLE IF NOT EXISTS event_reservation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(100) NOT NULL,
    event_organizer VARCHAR(100) NOT NULL,
    event_organizer_contact VARCHAR(100) NOT NULL,
    event_expected_attendees INT NOT NULL,
    event_description TEXT,
    event_venue_id INT, -- Already NULLable
    event_status ENUM('Pending', 'Checked In', 'Checked Out', 'Cancelled') DEFAULT 'Pending',
    event_checkin DATETIME,
    event_checkout DATETIME,
    event_hour_count ENUM('8', '16', '24') DEFAULT '8',
    event_days_count INT DEFAULT 1, -- Added default value
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_venue_id (event_venue_id),
    FOREIGN KEY (event_venue_id) REFERENCES event_venues(id) ON DELETE SET NULL
);

SELECT 'Event reservations table created successfully!' AS Status;

-- BILLING TRANSACTIONS TABLE
CREATE TABLE IF NOT EXISTS event_billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('Room Charge', 'Event Charge', 'Refund') DEFAULT 'Room Charge',
    event_reservation_id INT, -- Removed NULL to align with typical schema
    payment_amount DECIMAL(10,2) COMMENT 'Money the customer paid',
    balance DECIMAL(10,2) COMMENT 'Amount to be paid',
    `change` DECIMAL(10,2) COMMENT 'payment_amount - balance (calculated)',
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') DEFAULT 'Cash',
    status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT, -- Removed redundant NULL
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_reservation_id (event_reservation_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status),
    FOREIGN KEY (event_reservation_id) REFERENCES event_reservation(id) ON DELETE SET NULL
);

SELECT 'Billing transactions table created successfully!' AS Status;