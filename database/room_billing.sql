USE hmscore1;

-- ROOM BILLING TABLE (For room charges and payments)
CREATE TABLE IF NOT EXISTS room_billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('Room Charge', 'Event Charge', 'Refund') DEFAULT 'Room Charge',
    reservation_id INT,
    room_id INT,
    payment_amount DECIMAL(10,2),
    balance DECIMAL(10,2),
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') DEFAULT 'Cash',
    billing_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_reservation_id (reservation_id),
    INDEX idx_room_id (room_id),
    INDEX idx_billing_status (billing_status),
    INDEX idx_transaction_date (transaction_date),

    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

-- Sample Data
INSERT INTO room_billing (transaction_type, reservation_id, room_id, payment_amount, balance, payment_method, billing_status, transaction_date, notes) VALUES
('Room Charge', 1, 1, 1500.00, 0.00, 'Cash', 'Paid', '2024-01-03 12:00:00', 'Full payment for single room'),
('Room Charge', 2, 2, 2500.00, 0.00, 'Card', 'Paid', '2024-01-08 12:00:00', 'Full payment for double room'),
('Room Charge', 3, 7, 2500.00, 0.00, 'GCash', 'Paid', '2024-02-03 12:00:00', 'Full payment for double room'),
('Room Charge', 4, 3, 3500.00, 0.00, 'Bank Transfer', 'Paid', '2024-01-14 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 5, 8, 2500.00, 0.00, 'Cash', 'Paid', '2024-02-12 12:00:00', 'Full payment for double room'),
('Room Charge', 6, 13, 3500.00, 0.00, 'Card', 'Paid', '2024-03-04 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 7, 4, 4500.00, 0.00, 'GCash', 'Paid', '2024-01-20 12:00:00', 'Full payment for suite'),
('Room Charge', 8, 9, 2500.00, 0.00, 'Bank Transfer', 'Paid', '2024-02-17 12:00:00', 'Full payment for double room'),
('Room Charge', 9, 14, 3500.00, 0.00, 'Cash', 'Paid', '2024-03-18 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 10, 19, 4500.00, 0.00, 'Card', 'Paid', '2024-04-05 12:00:00', 'Full payment for suite'),
('Room Charge', 11, 5, 1500.00, 0.00, 'GCash', 'Paid', '2024-01-22 12:00:00', 'Full payment for single room'),
('Room Charge', 12, 12, 3500.00, 0.00, 'Bank Transfer', 'Paid', '2024-01-28 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 13, 17, 4500.00, 0.00, 'Cash', 'Paid', '2024-02-07 12:00:00', 'Full payment for suite'),
('Room Charge', 14, 18, 4500.00, 0.00, 'Card', 'Paid', '2024-02-03 12:00:00', 'Full payment for suite'),
('Room Charge', 15, 23, 3500.00, 0.00, 'GCash', 'Paid', '2024-02-22 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 16, 3, 3500.00, 0.00, 'Bank Transfer', 'Paid', '2024-03-08 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 17, 24, 3500.00, 0.00, 'Cash', 'Paid', '2024-02-06 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 18, 4, 4500.00, 0.00, 'Card', 'Paid', '2024-03-03 12:00:00', 'Full payment for suite'),
('Room Charge', 19, 9, 2500.00, 0.00, 'GCash', 'Paid', '2024-04-04 12:00:00', 'Full payment for double room'),
('Room Charge', 20, 14, 3500.00, 0.00, 'Bank Transfer', 'Paid', '2024-05-05 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 21, 10, 2500.00, 0.00, 'Cash', 'Paid', '2024-02-12 12:00:00', 'Full payment for double room'),
('Room Charge', 22, 22, 3500.00, 0.00, 'Card', 'Paid', '2024-02-18 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 23, 2, 2500.00, 0.00, 'GCash', 'Paid', '2024-03-12 12:00:00', 'Full payment for double room'),
('Room Charge', 24, 13, 3500.00, 0.00, 'Bank Transfer', 'Paid', '2024-02-24 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 25, 18, 4500.00, 0.00, 'Cash', 'Paid', '2024-03-22 12:00:00', 'Full payment for suite'),
('Room Charge', 26, 23, 3500.00, 0.00, 'Card', 'Paid', '2024-04-13 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 27, 19, 4500.00, 0.00, 'GCash', 'Paid', '2024-03-01 12:00:00', 'Full payment for suite'),
('Room Charge', 28, 4, 4500.00, 0.00, 'Bank Transfer', 'Paid', '2024-04-03 12:00:00', 'Full payment for suite'),
('Room Charge', 29, 9, 2500.00, 0.00, 'Cash', 'Paid', '2024-05-04 12:00:00', 'Full payment for double room'),
('Room Charge', 30, 14, 3500.00, 0.00, 'Card', 'Paid', '2024-06-05 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 31, 15, 4500.00, 0.00, 'GCash', 'Paid', '2024-03-03 12:00:00', 'Full payment for suite'),
('Room Charge', 32, 17, 4500.00, 0.00, 'Bank Transfer', 'Paid', '2024-03-08 12:00:00', 'Full payment for suite'),
('Room Charge', 33, 22, 3500.00, 0.00, 'Cash', 'Paid', '2024-04-07 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 34, 8, 2500.00, 0.00, 'Card', 'Paid', '2024-03-14 12:00:00', 'Full payment for double room'),
('Room Charge', 35, 13, 3500.00, 0.00, 'GCash', 'Paid', '2024-04-12 12:00:00', 'Full payment for deluxe room'),
('Room Charge', 36, 18, 4500.00, 0.00, 'Bank Transfer', 'Paid', '2024-05-13 12:00:00', 'Full payment for suite');