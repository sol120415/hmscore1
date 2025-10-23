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

-- Sample Data for Event Venues
INSERT INTO event_venues (venue_name, venue_address, venue_capacity, venue_rate, venue_description, venue_status) VALUES
('Grand Ballroom', '123 Main St, Manila, Philippines', 200, 5000.00, 'Elegant ballroom for weddings and corporate events', 'Available'),
('Conference Hall A', '456 Business Ave, Makati, Philippines', 150, 3000.00, 'Modern conference hall with AV equipment', 'Available'),
('Garden Pavilion', '789 Park Rd, Cebu, Philippines', 100, 2500.00, 'Outdoor venue with garden views', 'Available'),
('Executive Suite', '101 Corporate Blvd, Quezon City, Philippines', 50, 4000.00, 'Private suite for small meetings', 'Available'),
('Rooftop Terrace', '202 Skyline Dr, Davao, Philippines', 80, 3500.00, 'Rooftop venue with city views', 'Available');

-- Sample Data for Event Reservations
INSERT INTO event_reservation (event_title, event_organizer, event_organizer_contact, event_expected_attendees, event_description, event_venue_id, event_status, event_checkin, event_checkout, event_hour_count) VALUES
('Corporate Seminar', 'ABC Corp', 'contact@abc.com', 120, 'Annual company seminar', 1, 'Checked Out', '2024-01-15 09:00:00', '2024-01-15 17:00:00', 8),
('Wedding Reception', 'Juan & Maria', 'juan.maria@email.com', 150, 'Evening wedding reception', 1, 'Checked Out', '2024-02-20 18:00:00', '2024-02-20 23:00:00', 5),
('Product Launch', 'Tech Innovations', 'info@techinnov.com', 80, 'New product launch event', 2, 'Checked Out', '2024-03-10 10:00:00', '2024-03-10 16:00:00', 6),
('Birthday Party', 'Elena Castillo', 'elena.castillo@email.com', 60, '50th birthday celebration', 3, 'Checked Out', '2024-04-05 14:00:00', '2024-04-05 20:00:00', 6),
('Team Building', 'XYZ Company', 'hr@xyz.com', 40, 'Company team building activity', 4, 'Checked Out', '2024-05-12 08:00:00', '2024-05-12 18:00:00', 10),
('Charity Gala', 'Philippine Red Cross', 'events@redcross.ph', 200, 'Annual charity fundraising gala', 1, 'Checked Out', '2024-06-01 19:00:00', '2024-06-02 01:00:00', 6),
('Conference Workshop', 'Business Leaders Inc', 'workshops@bizleaders.com', 100, 'Leadership development workshop', 2, 'Checked Out', '2024-07-08 09:00:00', '2024-07-08 17:00:00', 8),
('Family Reunion', 'Santos Family', 'santos.family@email.com', 70, 'Extended family reunion', 3, 'Checked Out', '2024-08-15 12:00:00', '2024-08-15 22:00:00', 10),
('Music Concert', 'Local Artists', 'bookings@localartists.ph', 90, 'Live music performance', 5, 'Checked Out', '2024-09-20 20:00:00', '2024-09-20 23:00:00', 3),
('Business Networking', 'Chamber of Commerce', 'networking@chamber.ph', 50, 'Monthly networking event', 4, 'Checked Out', '2024-10-10 17:00:00', '2024-10-10 21:00:00', 4);

-- Sample Data for Event Billing (10 transactions)
INSERT INTO event_billing (transaction_type, reservation_id, venue_id, payment_amount, balance, payment_method, billing_status, transaction_date, notes) VALUES
('Venue Charge', 1, 1, 40000.00, 0.00, 'Bank Transfer', 'Paid', '2024-01-15 17:00:00', 'Full payment for 8-hour corporate seminar'),
('Venue Charge', 2, 1, 25000.00, 0.00, 'Card', 'Paid', '2024-02-20 23:00:00', 'Full payment for 5-hour wedding reception'),
('Venue Charge', 3, 2, 18000.00, 0.00, 'GCash', 'Paid', '2024-03-10 16:00:00', 'Full payment for 6-hour product launch'),
('Venue Charge', 4, 3, 15000.00, 0.00, 'Cash', 'Paid', '2024-04-05 20:00:00', 'Full payment for 6-hour birthday party'),
('Venue Charge', 5, 4, 40000.00, 0.00, 'Bank Transfer', 'Paid', '2024-05-12 18:00:00', 'Full payment for 10-hour team building'),
('Venue Charge', 6, 1, 30000.00, 0.00, 'Card', 'Paid', '2024-06-02 01:00:00', 'Full payment for 6-hour charity gala'),
('Venue Charge', 7, 2, 24000.00, 0.00, 'GCash', 'Paid', '2024-07-08 17:00:00', 'Full payment for 8-hour conference workshop'),
('Venue Charge', 8, 3, 25000.00, 0.00, 'Cash', 'Paid', '2024-08-15 22:00:00', 'Full payment for 10-hour family reunion'),
('Venue Charge', 9, 5, 10500.00, 0.00, 'Bank Transfer', 'Paid', '2024-09-20 23:00:00', 'Full payment for 3-hour music concert'),
('Venue Charge', 10, 4, 16000.00, 0.00, 'Card', 'Paid', '2024-10-10 21:00:00', 'Full payment for 4-hour business networking');
