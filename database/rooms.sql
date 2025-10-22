USE hmscore1;

-- ROOMS TABLE 
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') DEFAULT 'Single',
    room_floor VARCHAR(10) NOT NULL,
    room_status ENUM('Vacant', 'Occupied', 'Maintenance', 'Cleaning') DEFAULT 'Vacant',
    room_rate DECIMAL(10,2) AS (CASE room_type
        WHEN 'Single' THEN 1500.00
        WHEN 'Double' THEN 2500.00
        WHEN 'Deluxe' THEN 3500.00
        WHEN 'Suite' THEN 4500.00
        ELSE 0 END) VIRTUAL,
    room_max_guests INT AS (CASE room_type
        WHEN 'Single' THEN 1
        WHEN 'Double' THEN 2
        WHEN 'Deluxe' THEN 3
        WHEN 'Suite' THEN 4
        ELSE 0 END) VIRTUAL,
    room_amenities TEXT AS (CASE room_type
        WHEN 'Single' THEN 'Bathroom'
        WHEN 'Double' THEN 'TV, Bathroom'
        WHEN 'Deluxe' THEN 'TV, Air Conditioning, Bathroom'
        WHEN 'Suite' THEN 'TV, Air Conditioning, Bathroom, Kitchen'
        ELSE '' END) VIRTUAL,
    room_last_cleaned TIMESTAMP NULL,
    room_maintenance_notes TEXT NULL,
    room_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    room_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_room_number (room_number),
    INDEX idx_room_created_at (room_created_at)
);

-- Sample data
INSERT INTO rooms (room_number, room_type, room_floor, room_status, room_last_cleaned, room_maintenance_notes, room_created_at, room_updated_at) VALUES
('101', 'Single', '1', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('102', 'Double', '1', 'Occupied', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('103', 'Deluxe', '2', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('104', 'Suite', '2', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('105', 'Single', '3', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('201', 'Single', '2', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('202', 'Double', '2', 'Occupied', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('203', 'Deluxe', '2', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('204', 'Suite', '2', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('205', 'Single', '2', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('301', 'Single', '3', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('302', 'Double', '3', 'Occupied', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('303', 'Deluxe', '3', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('304', 'Suite', '3', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('305', 'Single', '3', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('401', 'Single', '4', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('402', 'Double', '4', 'Occupied', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('403', 'Deluxe', '4', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('404', 'Suite', '4', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('405', 'Single', '4', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('501', 'Single', '5', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('502', 'Double', '5', 'Occupied', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('503', 'Deluxe', '5', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('504', 'Suite', '5', 'Maintenance', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('505', 'Single', '5', 'Vacant', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);