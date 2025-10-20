USE hmscore1;

-- EVENT VENUES TABLE
CREATE TABLE IF NOT EXISTS event_venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_name VARCHAR(100) NOT NULL,
    venue_address VARCHAR(255) NOT NULL,
    venue_capacity INT NOT NULL, -- Removed invalid CASE syntax
    venue_rate DECIMAL(10,2), -- Removed invalid AS/CASE syntax, made non-generated column
    venue_description TEXT,
    venue_status ENUM('Available', 'Booked', 'Maintenance') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- EVENT RESERVATIONS TABLE
CREATE TABLE IF NOT EXISTS event_reservation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(100) NOT NULL,
    event_organizer VARCHAR(100) NOT NULL,
    event_organizer_contact VARCHAR(100) NOT NULL,
    event_expected_attendees INT NOT NULL,
    event_description TEXT,
    event_venue_id INT, -- Reference to event_venues table
    event_status ENUM('Pending', 'Checked In', 'Checked Out', 'Cancelled', 'Archived') DEFAULT 'Pending',
    event_checkin DATETIME,
    event_checkout DATETIME,
    event_hour_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_venue_id (event_venue_id),
    FOREIGN KEY (event_venue_id) REFERENCES event_venues(id) ON DELETE SET NULL
);

-- EVENT BILLING TABLE (For event charges and payments)
CREATE TABLE IF NOT EXISTS event_billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('Event Charge', 'Venue Charge', 'Refund') DEFAULT 'Event Charge',
    reservation_id INT,
    venue_id INT,
    payment_amount DECIMAL(10,2),
    balance DECIMAL(10,2),
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') DEFAULT 'Cash',
    billing_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_reservation_id (reservation_id),
    INDEX idx_venue_id (venue_id),
    INDEX idx_billing_status (billing_status),
    INDEX idx_transaction_date (transaction_date),

    FOREIGN KEY (reservation_id) REFERENCES event_reservation(id) ON DELETE SET NULL,
    FOREIGN KEY (venue_id) REFERENCES event_venues(id) ON DELETE SET NULL
);
