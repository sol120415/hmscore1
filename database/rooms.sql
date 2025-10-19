USE hmscore1;

-- ROOMS TABLE 
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') DEFAULT 'Single',
    room_floor VARCHAR(10) NOT NULL,
    room_status ENUM('Vacant', 'Occupied', 'Cleaning', 'Maintenance', 'Reserved') DEFAULT 'Vacant',
    room_rate DECIMAL(10,2) AS (CASE room_type
        WHEN 'Single' THEN 1500.00
        WHEN 'Double' THEN 2500.00
        WHEN 'Deluxe' THEN 3500.00
        WHEN 'Suite' THEN 4500.00
        ELSE 0 END) VIRTUAL,
    room_max_guests INT DEFAULT 2,
    room_amenities TEXT NULL,
    room_last_cleaned TIMESTAMP NULL,
    room_maintenance_notes TEXT NULL,
    room_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    room_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_room_number (room_number),
    INDEX idx_room_created_at (room_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ROOMS TABLE


-- sample data
INSERT INTO rooms (room_number, room_type, room_floor, room_status, room_rate, room_max_guests, room_amenities, room_last_cleaned, room_maintenance_notes, room_created_at, room_updated_at) VALUES
('101', 'Single', '1', 'Vacant', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('102', 'Double', '1', 'Occupied', 2500.00, 2, 'TV, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('103', 'Deluxe', '2', 'Cleaning', 3500.00, 2, 'TV, Air Conditioning, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('104', 'Suite', '2', 'Maintenance', 4500.00, 2, 'TV, Air Conditioning, Bathroom, Kitchen', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('105', 'Single', '3', 'Reserved', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('201', 'Single', '2', 'Vacant', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('202', 'Double', '2', 'Occupied', 2500.00, 2, 'TV, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('203', 'Deluxe', '2', 'Cleaning', 3500.00, 2, 'TV, Air Conditioning, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('204', 'Suite', '2', 'Maintenance', 4500.00, 2, 'TV, Air Conditioning, Bathroom, Kitchen', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('205', 'Single', '2', 'Reserved', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('301', 'Single', '3', 'Vacant', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('302', 'Double', '3', 'Occupied', 2500.00, 2, 'TV, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('303', 'Deluxe', '3', 'Cleaning', 3500.00, 2, 'TV, Air Conditioning, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('304', 'Suite', '3', 'Maintenance', 4500.00, 2, 'TV, Air Conditioning, Bathroom, Kitchen', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('305', 'Single', '3', 'Reserved', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('401', 'Single', '4', 'Vacant', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('402', 'Double', '4', 'Occupied', 2500.00, 2, 'TV, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('403', 'Deluxe', '4', 'Cleaning', 3500.00, 2, 'TV, Air Conditioning, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('404', 'Suite', '4', 'Maintenance', 4500.00, 2, 'TV, Air Conditioning, Bathroom, Kitchen', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('405', 'Single', '4', 'Reserved', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('501', 'Single', '5', 'Vacant', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('502', 'Double', '5', 'Occupied', 2500.00, 2, 'TV, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('503', 'Deluxe', '5', 'Cleaning', 3500.00, 2, 'TV, Air Conditioning, Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('504', 'Suite', '5', 'Maintenance', 4500.00, 2, 'TV, Air Conditioning, Bathroom, Kitchen', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('505', 'Single', '5', 'Reserved', 1500.00, 2, 'Bathroom', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);