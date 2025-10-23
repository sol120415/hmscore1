USE hmscore1;

-- RESERVATIONS TABLE (Booking Management)
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guest_id INT NULL,
    room_id INT NULL,
    reservation_type ENUM('Room', 'Event') NOT NULL,
    reservation_date DATETIME NOT NULL,
    reservation_hour_count INT NOT NULL,
    check_in_date DATETIME NOT NULL,
    check_out_date DATETIME NOT NULL,
    reservation_status ENUM('Pending', 'Checked In', 'Checked Out', 'Cancelled', 'Archived') DEFAULT 'Pending',
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
);

-- Sample Data
INSERT INTO reservations (guest_id, room_id, reservation_type, reservation_date, reservation_hour_count, check_in_date, check_out_date, reservation_status) VALUES
(1, 1, 'Room', '2024-01-01 10:00:00', 24, '2024-01-02 14:00:00', '2024-01-03 12:00:00', 'Checked Out'),
(2, 2, 'Room', '2024-01-05 10:00:00', 48, '2024-01-06 14:00:00', '2024-01-08 12:00:00', 'Checked Out'),
(2, 7, 'Room', '2024-02-01 10:00:00', 24, '2024-02-02 14:00:00', '2024-02-03 12:00:00', 'Checked Out'),
(3, 3, 'Room', '2024-01-10 10:00:00', 72, '2024-01-11 14:00:00', '2024-01-14 12:00:00', 'Checked Out'),
(3, 8, 'Room', '2024-02-10 10:00:00', 24, '2024-02-11 14:00:00', '2024-02-12 12:00:00', 'Checked Out'),
(3, 13, 'Room', '2024-03-01 10:00:00', 48, '2024-03-02 14:00:00', '2024-03-04 12:00:00', 'Checked Out'),
(4, 4, 'Room', '2024-01-15 10:00:00', 96, '2024-01-16 14:00:00', '2024-01-20 12:00:00', 'Checked Out'),
(4, 9, 'Room', '2024-02-15 10:00:00', 24, '2024-02-16 14:00:00', '2024-02-17 12:00:00', 'Checked Out'),
(4, 14, 'Room', '2024-03-15 10:00:00', 48, '2024-03-16 14:00:00', '2024-03-18 12:00:00', 'Checked Out'),
(4, 19, 'Room', '2024-04-01 10:00:00', 72, '2024-04-02 14:00:00', '2024-04-05 12:00:00', 'Checked Out'),
(5, 5, 'Room', '2024-01-20 10:00:00', 24, '2024-01-21 14:00:00', '2024-01-22 12:00:00', 'Checked Out'),
(6, 12, 'Room', '2024-01-25 10:00:00', 48, '2024-01-26 14:00:00', '2024-01-28 12:00:00', 'Checked Out'),
(6, 17, 'Room', '2024-02-05 10:00:00', 24, '2024-02-06 14:00:00', '2024-02-07 12:00:00', 'Checked Out'),
(7, 18, 'Room', '2024-01-30 10:00:00', 72, '2024-01-31 14:00:00', '2024-02-03 12:00:00', 'Checked Out'),
(7, 23, 'Room', '2024-02-20 10:00:00', 24, '2024-02-21 14:00:00', '2024-02-22 12:00:00', 'Checked Out'),
(7, 3, 'Room', '2024-03-05 10:00:00', 48, '2024-03-06 14:00:00', '2024-03-08 12:00:00', 'Checked Out'),
(8, 24, 'Room', '2024-02-01 10:00:00', 96, '2024-02-02 14:00:00', '2024-02-06 12:00:00', 'Checked Out'),
(8, 4, 'Room', '2024-03-01 10:00:00', 24, '2024-03-02 14:00:00', '2024-03-03 12:00:00', 'Checked Out'),
(8, 9, 'Room', '2024-04-01 10:00:00', 48, '2024-04-02 14:00:00', '2024-04-04 12:00:00', 'Checked Out'),
(8, 14, 'Room', '2024-05-01 10:00:00', 72, '2024-05-02 14:00:00', '2024-05-05 12:00:00', 'Checked Out'),
(9, 10, 'Room', '2024-02-10 10:00:00', 24, '2024-02-11 14:00:00', '2024-02-12 12:00:00', 'Checked Out'),
(10, 22, 'Room', '2024-02-15 10:00:00', 48, '2024-02-16 14:00:00', '2024-02-18 12:00:00', 'Checked Out'),
(10, 2, 'Room', '2024-03-10 10:00:00', 24, '2024-03-11 14:00:00', '2024-03-12 12:00:00', 'Checked Out'),
(11, 13, 'Room', '2024-02-20 10:00:00', 72, '2024-02-21 14:00:00', '2024-02-24 12:00:00', 'Checked Out'),
(11, 18, 'Room', '2024-03-20 10:00:00', 24, '2024-03-21 14:00:00', '2024-03-22 12:00:00', 'Checked Out'),
(11, 23, 'Room', '2024-04-10 10:00:00', 48, '2024-04-11 14:00:00', '2024-04-13 12:00:00', 'Checked Out'),
(12, 19, 'Room', '2024-02-25 10:00:00', 96, '2024-02-26 14:00:00', '2024-03-01 12:00:00', 'Checked Out'),
(12, 4, 'Room', '2024-04-01 10:00:00', 24, '2024-04-02 14:00:00', '2024-04-03 12:00:00', 'Checked Out'),
(12, 9, 'Room', '2024-05-01 10:00:00', 48, '2024-05-02 14:00:00', '2024-05-04 12:00:00', 'Checked Out'),
(12, 14, 'Room', '2024-06-01 10:00:00', 72, '2024-06-02 14:00:00', '2024-06-05 12:00:00', 'Checked Out'),
(13, 15, 'Room', '2024-03-01 10:00:00', 24, '2024-03-02 14:00:00', '2024-03-03 12:00:00', 'Checked Out'),
(14, 17, 'Room', '2024-03-05 10:00:00', 48, '2024-03-06 14:00:00', '2024-03-08 12:00:00', 'Checked Out'),
(14, 22, 'Room', '2024-04-05 10:00:00', 24, '2024-04-06 14:00:00', '2024-04-07 12:00:00', 'Checked Out'),
(15, 8, 'Room', '2024-03-10 10:00:00', 72, '2024-03-11 14:00:00', '2024-03-14 12:00:00', 'Checked Out'),
(15, 13, 'Room', '2024-04-10 10:00:00', 24, '2024-04-11 14:00:00', '2024-04-12 12:00:00', 'Checked Out'),
(15, 18, 'Room', '2024-05-10 10:00:00', 48, '2024-05-11 14:00:00', '2024-05-13 12:00:00', 'Checked Out');

