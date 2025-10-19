USE hmscore1;

-- RESERVATIONS TABLE (Booking Management)
CREATE TABLE IF NOT EXISTS reservations (
    id VARCHAR(50) PRIMARY KEY,
    guest_id INT NULL,
    room_id INT NULL,
    reservation_type ENUM('Room', 'Event') NOT NULL,
    reservation_date DATETIME NOT NULL,
    reservation_hour_count ENUM('8','16','24') DEFAULT '8',
    reservation_days_count INT,
    check_in_date DATETIME NOT NULL,
    check_out_date DATETIME NOT NULL,
    reservation_status ENUM('Pending', 'Checked In', 'Checked Out', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id),
    INDEX idx_reservation_date (reservation_date),
    INDEX idx_check_in_date (check_in_date),
    INDEX idx_check_out_date (check_out_date),
    INDEX idx_reservation_status (reservation_status),
    INDEX idx_reservation_type (reservation_type),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

