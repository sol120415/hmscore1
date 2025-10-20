USE hmscore1;

-- ROOM BILLING TABLE (For room charges and payments)
CREATE TABLE IF NOT EXISTS room_billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('Room Charge', 'Event Charge', 'Refund') DEFAULT 'Room Charge',
    reservation_id INT,
    room_id INT,
    guest_id INT,
    item_description VARCHAR(255),
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    payment_amount DECIMAL(10,2),
    balance DECIMAL(10,2),
    `change` DECIMAL(10,2),
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') DEFAULT 'Cash',
    billing_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_reservation_id (reservation_id),
    INDEX idx_room_id (room_id),
    INDEX idx_guest_id (guest_id),
    INDEX idx_billing_status (billing_status),
    INDEX idx_transaction_date (transaction_date),

    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL
);