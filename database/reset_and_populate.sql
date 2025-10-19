
USE hmscore1;

-- Clear all existing data (in correct order due to foreign keys)
DELETE FROM campaign_performance;
DELETE FROM promotional_offers;
DELETE FROM marketing_campaigns;
DELETE FROM channel_bookings;
DELETE FROM channels;
DELETE FROM event_billing;
DELETE FROM event_reservation;
DELETE FROM event_venues;
DELETE FROM room_billing;
DELETE FROM inventory_movements;
DELETE FROM items;
DELETE FROM housekeeping_supplies;
DELETE FROM housekeeping;
DELETE FROM reservations;
DELETE FROM rooms;
DELETE FROM guests;
DELETE FROM housekeepers;
DELETE FROM users;

-- Reset auto-increment counters
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE guests AUTO_INCREMENT = 1;
ALTER TABLE rooms AUTO_INCREMENT = 1;
ALTER TABLE reservations AUTO_INCREMENT = 1;
ALTER TABLE housekeepers AUTO_INCREMENT = 1;
ALTER TABLE housekeeping AUTO_INCREMENT = 1;
ALTER TABLE housekeeping_supplies AUTO_INCREMENT = 1;
ALTER TABLE items AUTO_INCREMENT = 1;
ALTER TABLE inventory_movements AUTO_INCREMENT = 1;
ALTER TABLE room_billing AUTO_INCREMENT = 1;
ALTER TABLE event_venues AUTO_INCREMENT = 1;
ALTER TABLE event_reservation AUTO_INCREMENT = 1;
ALTER TABLE event_billing AUTO_INCREMENT = 1;
ALTER TABLE channels AUTO_INCREMENT = 1;
ALTER TABLE channel_bookings AUTO_INCREMENT = 1;
ALTER TABLE marketing_campaigns AUTO_INCREMENT = 1;
ALTER TABLE promotional_offers AUTO_INCREMENT = 1;
ALTER TABLE campaign_performance AUTO_INCREMENT = 1;

-- Insert users
INSERT INTO users (username, email, password, role, status, created_at) VALUES
('admin', 'admin@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW()), -- password: password
('manager', 'manager@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active', NOW()), -- password: password
('staff', 'staff@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', NOW()); -- password: password

-- Insert guests (with VIP status based on stay_count >= 5 OR total_spend >= 1000)
INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, notes, loyalty_status, stay_count, total_spend, created_at) VALUES
('John', 'Doe', 'john.doe@example.com', '1234567890', '123 Main St', 'Anytown', 'USA', 'Passport', 'AB123456', '1990-01-01', 'American', 'Regular guest', 'Regular', 2, 250.00, NOW()),
('Jane', 'Smith', 'jane.smith@example.com', '0987654321', '456 Elm St', 'Othertown', 'Canada', 'Driver License', 'CD789012', '1985-05-15', 'Canadian', 'Frequent visitor', 'VIP', 6, 1200.00, NOW()),
('Bob', 'Johnson', 'bob.johnson@example.com', '1122334455', '789 Oak St', 'Thirdtown', 'UK', 'National ID', 'ID123456', '1978-10-20', 'British', 'Business traveler', 'Regular', 1, 150.00, NOW()),
('Alice', 'Williams', 'alice.williams@example.com', '2233445566', '101 Pine St', 'Fourthtown', 'Australia', 'Passport', 'AE123456', '1992-08-25', 'Australian', 'Tourist', 'VIP', 8, 1800.00, NOW()),
('David', 'Brown', 'david.brown@example.com', '3344556677', '202 Maple St', 'Fifthtown', 'Germany', 'Driver License', 'DB123456', '1988-03-10', 'German', 'Conference attendee', 'Regular', 3, 400.00, NOW()),
('Emily', 'Davis', 'emily.davis@example.com', '4455667788', '303 Birch St', 'Sixthtown', 'France', 'Passport', 'FD123456', '1995-06-12', 'French', 'Honeymoon couple', 'VIP', 5, 950.00, NOW()),
('Michael', 'Wilson', 'michael.wilson@example.com', '5566778899', '404 Willow St', 'Seventhtown', 'Italy', 'Driver License', 'DW123456', '1982-11-25', 'Italian', 'Family vacation', 'Regular', 2, 300.00, NOW()),
('Sarah', 'Taylor', 'sarah.taylor@example.com', '6677889900', '505 Cedar St', 'Eighthtown', 'Spain', 'Passport', 'ST123456', '1990-02-18', 'Spanish', 'Weekend getaway', 'VIP', 7, 1400.00, NOW()),
('William', 'Anderson', 'william.anderson@example.com', '7788990011', '606 Pine St', 'Ninethtown', 'Japan', 'Driver License', 'WA123456', '1987-07-22', 'Japanese', 'Business trip', 'Regular', 4, 600.00, NOW()),
('Olivia', 'Green', 'olivia.green@example.com', '8899001122', '707 Oak St', 'Tenth town', 'China', 'Passport', 'OG123456', '1993-04-28', 'Chinese', 'Shopping trip', 'VIP', 9, 2100.00, NOW()),
('James', 'Miller', 'james.miller@example.com', '9900112233', '808 Elm St', 'Eleventhtown', 'Brazil', 'National ID', 'JM123456', '1980-12-05', 'Brazilian', 'Extended stay', 'VIP', 12, 2800.00, NOW()),
('Sophia', 'Garcia', 'sophia.garcia@example.com', '0011223344', '909 Maple St', 'Twelfthtown', 'Mexico', 'Driver License', 'SG123456', '1991-09-14', 'Mexican', 'Vacation', 'Regular', 1, 200.00, NOW()),
('Benjamin', 'Martinez', 'benjamin.martinez@example.com', '1122334455', '1010 Oak St', 'Thirteenth', 'Argentina', 'Passport', 'BM123456', '1986-04-30', 'Argentinian', 'Business conference', 'VIP', 10, 1900.00, NOW()),
('Isabella', 'Rodriguez', 'isabella.rodriguez@example.com', '2233445566', '1111 Pine St', 'Fourteenth', 'Colombia', 'National ID', 'IR123456', '1994-11-22', 'Colombian', 'Family reunion', 'Regular', 3, 450.00, NOW()),
('Lucas', 'Lopez', 'lucas.lopez@example.com', '3344556677', '1212 Cedar St', 'Fifteenth', 'Peru', 'Driver License', 'LL123456', '1989-07-08', 'Peruvian', 'Adventure trip', 'VIP', 6, 1100.00, NOW());

-- Insert housekeepers
INSERT INTO housekeepers (first_name, last_name, employee_id, email, phone, status, hire_date, specialty, shift_preference, max_rooms_per_day, notes, created_at) VALUES
('Maria', 'Santos', 'HK001', 'maria.santos@hotel.com', '1112223333', 'Active', '2023-01-15', 'Deep Cleaning', 'Morning', 8, 'Experienced housekeeper', NOW()),
('Carlos', 'Rodriguez', 'HK002', 'carlos.rodriguez@hotel.com', '2223334444', 'Active', '2023-02-20', 'Maintenance', 'Afternoon', 6, 'Skilled in repairs', NOW()),
('Ana', 'Martinez', 'HK003', 'ana.martinez@hotel.com', '3334445555', 'Active', '2023-03-10', 'General', 'Morning', 10, 'Fast worker', NOW()),
('Pedro', 'Gonzalez', 'HK004', 'pedro.gonzalez@hotel.com', '4445556666', 'Active', '2023-04-05', 'Deep Cleaning', 'Evening', 7, 'Detail-oriented', NOW()),
('Lucia', 'Fernandez', 'HK005', 'lucia.fernandez@hotel.com', '5556667777', 'Active', '2023-05-12', 'General', 'Flexible', 9, 'Reliable team member', NOW());

-- Insert rooms
INSERT INTO rooms (room_number, room_type, room_floor, room_status, room_max_guests, room_amenities, room_maintenance_notes, created_at) VALUES
('101', 'Single', 1, 'Vacant', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('102', 'Single', 1, 'Occupied', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('103', 'Double', 1, 'Vacant', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', NULL, NOW()),
('104', 'Double', 1, 'Cleaning', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', NULL, NOW()),
('105', 'Deluxe', 1, 'Vacant', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Balcony', NULL, NOW()),
('201', 'Single', 2, 'Vacant', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('202', 'Single', 2, 'Occupied', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('203', 'Double', 2, 'Vacant', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', NULL, NOW()),
('204', 'Double', 2, 'Maintenance', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', 'Fix leaking faucet', NOW()),
('205', 'Suite', 2, 'Vacant', 4, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Kitchen, Living Room', NULL, NOW()),
('301', 'Single', 3, 'Vacant', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('302', 'Double', 3, 'Occupied', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', NULL, NOW()),
('303', 'Deluxe', 3, 'Vacant', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Balcony', NULL, NOW()),
('304', 'Suite', 3, 'Vacant', 4, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Kitchen, Living Room', NULL, NOW()),
('305', 'Single', 3, 'Cleaning', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('401', 'Double', 4, 'Vacant', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', NULL, NOW()),
('402', 'Deluxe', 4, 'Occupied', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Balcony', NULL, NOW()),
('403', 'Suite', 4, 'Vacant', 4, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Kitchen, Living Room', NULL, NOW()),
('404', 'Single', 4, 'Vacant', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('405', 'Double', 4, 'Maintenance', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', 'Replace carpet', NOW()),
('501', 'Suite', 5, 'Vacant', 4, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Kitchen, Living Room, Jacuzzi', NULL, NOW()),
('502', 'Deluxe', 5, 'Occupied', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Balcony', NULL, NOW()),
('503', 'Double', 5, 'Vacant', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar', NULL, NOW()),
('504', 'Single', 5, 'Vacant', 1, 'WiFi, TV, Air Conditioning, Bathroom', NULL, NOW()),
('505', 'Deluxe', 5, 'Cleaning', 2, 'WiFi, TV, Air Conditioning, Bathroom, Mini Bar, Balcony', NULL, NOW());

-- Insert reservations (linked to existing guests and rooms)
INSERT INTO reservations (id, guest_id, room_id, reservation_type, reservation_date, reservation_hour_count, reservation_days_count, check_in_date, check_out_date, reservation_status, created_at) VALUES
('RES-20241019001', 2, 2, 'Room', NOW(), 0, 3, '2024-10-20 14:00:00', '2024-10-23 12:00:00', 'Checked In', NOW()),
('RES-20241019002', 4, 5, 'Room', NOW(), 0, 2, '2024-10-21 15:00:00', '2024-10-23 11:00:00', 'Pending', NOW()),
('RES-20241019003', 6, 8, 'Room', NOW(), 0, 1, '2024-10-19 16:00:00', '2024-10-20 12:00:00', 'Checked Out', NOW()),
('RES-20241019004', 8, 12, 'Room', NOW(), 0, 4, '2024-10-22 10:00:00', '2024-10-26 10:00:00', 'Pending', NOW()),
('RES-20241019005', 10, 14, 'Room', NOW(), 0, 2, '2024-10-20 09:00:00', '2024-10-22 09:00:00', 'Checked In', NOW()),
('RES-20241019006', 11, 16, 'Room', NOW(), 0, 5, '2024-10-23 14:00:00', '2024-10-28 12:00:00', 'Pending', NOW()),
('RES-20241019007', 13, 18, 'Room', NOW(), 0, 3, '2024-10-21 11:00:00', '2024-10-24 11:00:00', 'Checked In', NOW()),
('RES-20241019008', 15, 22, 'Room', NOW(), 0, 2, '2024-10-24 13:00:00', '2024-10-26 11:00:00', 'Pending', NOW()),
('RES-20241019009', NULL, NULL, 'Room', NOW(), 8, 0, '2024-10-19 18:00:00', '2024-10-20 02:00:00', 'Checked Out', NOW()),
('RES-20241019010', NULL, NULL, 'Room', NOW(), 16, 0, '2024-10-20 20:00:00', '2024-10-21 12:00:00', 'Pending', NOW());

-- Insert housekeeping tasks (linked to existing rooms and housekeepers)
INSERT INTO housekeeping (room_id, housekeeper_id, task_type, priority, status, scheduled_date, scheduled_time, estimated_duration_minutes, supervisor_notes, created_by, created_at) VALUES
(4, 1, 'Regular Cleaning', 'Normal', 'In Progress', '2024-10-19', '09:00:00', 60, 'Deep clean required', 1, NOW()),
(9, 2, 'Maintenance', 'High', 'Pending', '2024-10-19', '10:00:00', 120, 'Fix leaking faucet', 1, NOW()),
(15, 3, 'Regular Cleaning', 'Normal', 'Completed', '2024-10-19', '11:00:00', 45, 'Standard checkout cleaning', 1, NOW()),
(20, 4, 'Maintenance', 'Urgent', 'Pending', '2024-10-19', '14:00:00', 90, 'Replace carpet in room 405', 1, NOW()),
(25, 5, 'Regular Cleaning', 'Normal', 'In Progress', '2024-10-19', '15:00:00', 60, 'Post-checkout cleaning', 1, NOW()),
(1, 1, 'Inspection', 'Low', 'Pending', '2024-10-20', '08:00:00', 30, 'Routine inspection', 1, NOW()),
(6, 2, 'Regular Cleaning', 'Normal', 'Pending', '2024-10-20', '09:00:00', 45, 'Daily maintenance', 1, NOW()),
(11, 3, 'Deep Cleaning', 'Normal', 'Pending', '2024-10-20', '10:00:00', 90, 'Weekly deep clean', 1, NOW());

-- Insert housekeeping supplies
INSERT INTO housekeeping_supplies (supply_name, category, current_stock, unit_of_measure, minimum_stock_level, supplier, cost_per_unit, notes) VALUES
('All-Purpose Cleaner', 'Cleaning', 25.5, 'liters', 10, 'CleanCo Supplies', 15.50, 'Multi-surface cleaner for general use'),
('Glass Cleaner', 'Cleaning', 15.2, 'liters', 8, 'CleanCo Supplies', 12.75, 'Streak-free glass and mirror cleaner'),
('Disinfectant Spray', 'Cleaning', 12.8, 'liters', 5, 'CleanCo Supplies', 18.90, 'Hospital-grade disinfectant'),
('Toilet Bowl Cleaner', 'Cleaning', 18.0, 'liters', 6, 'CleanCo Supplies', 14.25, 'Concentrated toilet cleaner'),
('Floor Cleaner', 'Cleaning', 8.5, 'liters', 12, 'CleanCo Supplies', 22.00, 'Heavy-duty floor cleaning solution'),
('Carpet Shampoo', 'Cleaning', 6.2, 'liters', 4, 'CleanCo Supplies', 28.50, 'Professional carpet cleaning solution'),
('Vacuum Cleaner Bags', 'Equipment', 30, 'pieces', 10, 'Maintenance Plus', 8.75, 'HEPA filter replacement bags'),
('Light Bulbs (LED)', 'Equipment', 45, 'pieces', 20, 'Maintenance Plus', 12.00, 'Energy-efficient LED bulbs'),
('Air Freshener Refills', 'Equipment', 22, 'pieces', 15, 'Maintenance Plus', 9.50, 'Room freshener cartridges'),
('Batteries (AA)', 'Equipment', 80, 'pieces', 50, 'Maintenance Plus', 0.85, 'Alkaline batteries for remotes'),
('Extension Cords', 'Equipment', 12, 'pieces', 5, 'Maintenance Plus', 25.00, 'Heavy-duty extension cords'),
('Bed Sheets (Queen)', 'Linens', 45, 'pieces', 20, 'Linen World', 35.00, 'High-thread count cotton sheets'),
('Pillow Cases', 'Linens', 60, 'pieces', 25, 'Linen World', 8.50, 'Standard pillow cases'),
('Towels (Bath)', 'Linens', 80, 'pieces', 30, 'Linen World', 12.75, 'Absorbent cotton bath towels'),
('Towels (Hand)', 'Linens', 95, 'pieces', 40, 'Linen World', 6.25, 'Small hand towels for bathrooms'),
('Towels (Face)', 'Linens', 110, 'pieces', 45, 'Linen World', 4.50, 'Soft facial towels'),
('Blankets', 'Linens', 25, 'pieces', 10, 'Linen World', 45.00, 'Thermal blankets for cold weather'),
('Toilet Paper', 'Amenities', 200, 'pieces', 50, 'Hotel Essentials', 2.25, 'Premium 2-ply toilet paper'),
('Tissue Boxes', 'Amenities', 75, 'pieces', 30, 'Hotel Essentials', 3.50, 'Facial tissue boxes'),
('Shampoo (Individual)', 'Amenities', 180, 'pieces', 60, 'Hotel Essentials', 1.85, 'Travel-size shampoo bottles'),
('Conditioner (Individual)', 'Amenities', 165, 'pieces', 60, 'Hotel Essentials', 1.95, 'Travel-size conditioner'),
('Body Wash (Individual)', 'Amenities', 155, 'pieces', 60, 'Hotel Essentials', 2.10, 'Travel-size body wash'),
('Lotion (Individual)', 'Amenities', 140, 'pieces', 50, 'Hotel Essentials', 2.25, 'Travel-size hand lotion'),
('Coffee Pods (Regular)', 'Equipment', 85, 'pieces', 40, 'Beverage Distributors', 0.65, 'Premium coffee pods'),
('Coffee Pods (Decaf)', 'Equipment', 55, 'pieces', 25, 'Beverage Distributors', 0.70, 'Decaffeinated coffee pods'),
('Tea Bags', 'Equipment', 120, 'pieces', 50, 'Beverage Distributors', 0.35, 'Assorted tea bag selection'),
('Sugar Packets', 'Equipment', 300, 'pieces', 100, 'Beverage Distributors', 0.15, 'Individual sugar packets'),
('Coffee Creamer', 'Equipment', 45, 'pieces', 20, 'Beverage Distributors', 0.85, 'Individual creamer cups'),
('Hand Sanitizer', 'Cleaning', 3.2, 'liters', 8, 'CleanCo Supplies', 22.00, 'Alcohol-based hand sanitizer'),
('Laundry Detergent', 'Cleaning', 2.5, 'kg', 10, 'CleanCo Supplies', 18.50, 'Professional laundry detergent'),
('Out of Stock Item', 'Equipment', 0, 'pieces', 5, 'Test Supplier', 50.00, 'This item is out of stock for testing');

-- Insert inventory items
INSERT INTO items (item_name, item_description, item_category, unit_of_measure, current_stock, minimum_stock, maximum_stock, unit_cost, unit_price, supplier_id, item_status) VALUES
('Premium Towels', 'High-quality cotton towels', 'Linens', 150, 50, 200, 15.00, 25.00, NULL, 'Active'),
('Room Key Cards', 'Electronic key cards for rooms', 'Equipment', 500, 100, 1000, 2.50, 5.00, NULL, 'Active'),
('Mini Bar Snacks', 'Assorted snacks for mini bar', 'Amenities', 75, 25, 150, 45.00, 75.00, NULL, 'Active'),
('Cleaning Supplies Kit', 'Complete cleaning supplies kit', 'Cleaning', 30, 10, 50, 85.00, 120.00, NULL, 'Active'),
('Maintenance Tools', 'Basic maintenance toolkit', 'Equipment', 12, 5, 20, 150.00, 200.00, NULL, 'Active'),
('Office Supplies', 'General office supplies', 'Equipment', 45, 15, 75, 25.00, 40.00, NULL, 'Active'),
('Guest Amenities', 'Welcome kit for guests', 'Amenities', 200, 50, 300, 8.50, 15.00, NULL, 'Active'),
('Security Equipment', 'Security cameras and locks', 'Equipment', 25, 10, 40, 300.00, 450.00, NULL, 'Active'),
('HVAC Filters', 'Air conditioning filters', 'Equipment', 60, 20, 100, 12.00, 18.00, NULL, 'Active'),
('Plumbing Supplies', 'Basic plumbing repair kit', 'Equipment', 8, 3, 15, 95.00, 140.00, NULL, 'Active');

-- Insert inventory movements
INSERT INTO inventory_movements (item_id, movement_type, quantity, reason, movement_date, user_id, reference_id) VALUES
(1, 'IN', 50, 'Initial stock', NOW(), 1, 'STOCK-001'),
(2, 'IN', 200, 'Initial stock', NOW(), 1, 'STOCK-002'),
(3, 'IN', 25, 'Initial stock', NOW(), 1, 'STOCK-003'),
(4, 'IN', 10, 'Initial stock', NOW(), 1, 'STOCK-004'),
(5, 'IN', 5, 'Initial stock', NOW(), 1, 'STOCK-005'),
(1, 'OUT', 10, 'Room restocking', NOW(), 2, 'RESTOCK-001'),
(2, 'OUT', 5, 'Lost/damaged', NOW(), 2, 'DAMAGE-001'),
(3, 'OUT', 15, 'Guest consumption', NOW(), 2, 'USAGE-001');

-- Insert room billing transactions
INSERT INTO room_billing (transaction_type, reservation_id, room_id, guest_id, item_description, quantity, unit_price, total_amount, payment_amount, balance, payment_method, billing_status, transaction_date, notes) VALUES
('Room Charge', 'RES-20241019001', 2, 2, 'Room Rate - Deluxe Double', 1, 150.00, 150.00, 150.00, 0.00, 'Card', 'Paid', NOW(), 'Room charge for Jane Smith'),
('Room Charge', 'RES-20241019003', 8, 6, 'Room Rate - Single', 1, 80.00, 80.00, 80.00, 0.00, 'Cash', 'Paid', NOW(), 'Room charge for Emily Davis'),
('Room Charge', 'RES-20241019005', 14, 10, 'Room Rate - Suite', 1, 250.00, 250.00, 250.00, 0.00, 'Card', 'Paid', NOW(), 'Room charge for Olivia Green'),
('Room Charge', 'RES-20241019007', 18, 13, 'Room Rate - Deluxe Double', 1, 180.00, 180.00, 180.00, 0.00, 'GCash', 'Paid', NOW(), 'Room charge for Benjamin Martinez'),
('Event Charge', NULL, NULL, NULL, 'Conference Room Rental', 1, 500.00, 500.00, 500.00, 0.00, 'Bank Transfer', 'Paid', NOW(), 'Corporate event booking'),
('Room Charge', NULL, NULL, NULL, 'Mini Bar - Snacks', 2, 15.00, 30.00, 30.00, 0.00, 'Cash', 'Paid', NOW(), 'Walk-in guest mini bar'),
('Refund', 'RES-20241019002', 5, 4, 'Cancellation Refund', 1, -75.00, -75.00, -75.00, 0.00, 'Card', 'Refunded', NOW(), 'Partial refund for cancellation');

-- Insert event venues
INSERT INTO event_venues (venue_name, venue_address, venue_capacity, venue_rate, venue_description, venue_status) VALUES
('Theater', '123 Theater St, Downtown', 50, 5000.00, 'Intimate theater space perfect for small gatherings, presentations, and performances', 'Available'),
('Auditorium', '456 Auditorium Ave, Business District', 100, 8000.00, 'Large auditorium suitable for conferences and large events', 'Available'),
('Convention Center', '789 Convention Blvd, City Center', 200, 15000.00, 'Spacious convention center for large corporate events and exhibitions', 'Available'),
('Exhibition Hall', '101 Exhibition Rd, Industrial Park', 500, 25000.00, 'Massive exhibition hall for trade shows and large gatherings', 'Available');

-- Insert event reservations
INSERT INTO event_reservation (event_title, event_organizer, event_organizer_contact, event_expected_attendees, event_description, event_venue_id, event_status, event_checkin, event_checkout, event_hour_count, event_days_count) VALUES
('Corporate Conference', 'TechCorp Inc', 'john@techcorp.com', 75, 'Annual company conference with keynote speakers', 2, 'Pending', '2024-11-15 09:00:00', '2024-11-15 17:00:00', 8, 1),
('Wedding Reception', 'Smith Family', 'sarah.smith@email.com', 120, 'Evening wedding reception with dinner and dancing', 3, 'Checked In', '2024-11-20 18:00:00', '2024-11-20 23:00:00', 5, 0),
('Product Launch', 'Innovate Solutions', 'marketing@innovate.com', 200, 'New product launch event with demonstrations', 4, 'Pending', '2024-11-25 10:00:00', '2024-11-25 16:00:00', 6, 1),
('Charity Gala', 'Hope Foundation', 'events@hope.org', 150, 'Annual charity gala dinner and auction', 3, 'Checked Out', '2024-10-30 19:00:00', '2024-10-30 23:00:00', 4, 0),
('Business Seminar', 'Business Leaders Inc', 'info@businessleaders.com', 50, 'Leadership development seminar', 1, 'Pending', '2024-12-05 09:00:00', '2024-12-05 16:00:00', 7, 1);

-- Insert event billing transactions
INSERT INTO event_billing (event_reservation_id, payment_amount, balance, payment_method, status, transaction_date, notes) VALUES
(1, 8000.00, 0.00, 'Bank Transfer', 'Paid', NOW(), 'Full payment for corporate conference'),
(2, 15000.00, 0.00, 'Card', 'Paid', NOW(), 'Wedding reception venue rental'),
(3, 25000.00, 0.00, 'Bank Transfer', 'Paid', NOW(), 'Product launch exhibition hall'),
(4, 12000.00, 0.00, 'Card', 'Paid', NOW(), 'Charity gala convention center'),
(5, 5000.00, 0.00, 'Cash', 'Paid', NOW(), 'Business seminar theater rental');

-- Insert channels
INSERT INTO channels (channel_name, channel_type, contact_email, contact_phone, commission_rate, status, notes) VALUES
('Booking.com', 'OTA', 'support@booking.com', '+1-415-555-0100', 15.00, 'Active', 'Major OTA platform with global reach'),
('Expedia', 'OTA', 'partner@expedia.com', '+1-425-555-0200', 18.00, 'Active', 'Leading online travel agency'),
('Airbnb', 'OTA', 'host@airbnb.com', '+1-415-555-0300', 12.00, 'Pending', 'Vacation rental platform'),
('Direct Website', 'Direct', 'reservations@hotel.com', '+1-555-0400', 0.00, 'Active', 'Official hotel website'),
('Agoda', 'OTA', 'partners@agoda.com', '+66-2-555-0500', 14.00, 'Active', 'Asian OTA platform'),
('Priceline', 'OTA', 'api@priceline.com', '+1-203-555-0600', 16.00, 'Active', 'Express deals specialist'),
('Hotels.com', 'OTA', 'affiliates@hotels.com', '+1-469-555-0700', 17.00, 'Active', 'Expedia Group brand'),
('TripAdvisor', 'OTA', 'business@tripadvisor.com', '+1-617-555-0800', 13.00, 'Active', 'Review and booking platform'),
('Kayak', 'OTA', 'metasearch@kayak.com', '+1-203-555-0900', 11.00, 'Active', 'Metasearch engine'),
('Travelocity', 'OTA', 'partners@travelocity.com', '+1-682-555-1000', 15.00, 'Active', 'Sabre Holdings brand'),
('Orbitz', 'OTA', 'api@orbitz.com', '+1-312-555-1100', 16.00, 'Active', 'Travel booking website'),
('Hostelworld', 'OTA', 'partners@hostelworld.com', '+353-1-555-1200', 10.00, 'Active', 'Budget accommodation specialist');

-- Insert channel bookings
INSERT INTO channel_bookings (channel_id, booking_reference, guest_name, check_in_date, check_out_date, total_amount, commission_amount, booking_status) VALUES
(1, 'BK001', 'John Smith', '2024-01-15', '2024-01-17', 150.00, 22.50, 'Completed'),
(1, 'BK002', 'Jane Doe', '2024-01-16', '2024-01-18', 200.00, 30.00, 'Completed'),
(1, 'BK003', 'Bob Johnson', '2024-01-17', '2024-01-19', 175.00, 26.25, 'Confirmed'),
(1, 'BK004', 'Alice Brown', '2024-01-18', '2024-01-20', 220.00, 33.00, 'Completed'),
(1, 'BK005', 'Charlie Wilson', '2024-01-19', '2024-01-21', 190.00, 28.50, 'Confirmed'),
(2, 'EXP001', 'David Lee', '2024-01-20', '2024-01-22', 180.00, 32.40, 'Completed'),
(2, 'EXP002', 'Emma Davis', '2024-01-21', '2024-01-23', 165.00, 29.70, 'Completed'),
(2, 'EXP003', 'Frank Miller', '2024-01-22', '2024-01-24', 195.00, 35.10, 'Confirmed'),
(2, 'EXP004', 'Grace Taylor', '2024-01-23', '2024-01-25', 210.00, 37.80, 'Completed'),
(3, 'AB001', 'Henry Clark', '2024-01-25', '2024-01-27', 120.00, 14.40, 'Confirmed'),
(3, 'AB002', 'Iris Rodriguez', '2024-01-26', '2024-01-28', 135.00, 16.20, 'Confirmed'),
(3, 'AB003', 'Jack Martinez', '2024-01-27', '2024-01-29', 145.00, 17.40, 'Confirmed'),
(4, 'DIR001', 'Kevin Anderson', '2024-01-28', '2024-01-30', 160.00, 0.00, 'Completed'),
(4, 'DIR002', 'Laura Thomas', '2024-01-29', '2024-01-31', 175.00, 0.00, 'Completed'),
(4, 'DIR003', 'Michael Jackson', '2024-01-30', '2024-02-01', 185.00, 0.00, 'Confirmed'),
(5, 'AGO001', 'George Diaz', '2024-02-01', '2024-02-03', 140.00, 19.60, 'Completed'),
(5, 'AGO002', 'Helen Evans', '2024-02-02', '2024-02-04', 155.00, 21.70, 'Confirmed'),
(6, 'PRC001', 'Linda Ingram', '2024-02-03', '2024-02-05', 185.00, 29.60, 'Completed'),
(7, 'HTC001', 'Oscar Lopez', '2024-02-04', '2024-02-06', 200.00, 34.00, 'Completed'),
(8, 'TA001', 'Quinn Nelson', '2024-02-05', '2024-02-07', 165.00, 21.45, 'Confirmed');

-- Insert marketing campaigns
INSERT INTO marketing_campaigns (name, description, campaign_type, target_audience, start_date, end_date, budget, status, leads_generated, conversions, revenue_generated, roi_percentage, created_by) VALUES
('Summer Sale 2023', 'Summer season promotional campaign with special discounts', 'promotion', 'All customers, families, couples', '2023-06-01', '2023-08-31', 5000.00, 'completed', 45, 12, 8500.00, 70.00, 1),
('Winter Holiday Promo', 'Holiday season marketing campaign', 'seasonal', 'Holiday travelers, families', '2023-11-01', '2024-01-15', 8000.00, 'active', 67, 18, 12000.00, 50.00, 1),
('Loyalty Program Launch', 'Launch of new customer loyalty program', 'loyalty', 'Existing customers, VIP members', '2023-09-01', '2023-12-31', 3000.00, 'active', 89, 23, 6500.00, 116.67, 1),
('Black Friday Blitz', 'Black Friday promotional campaign', 'advertising', 'Online shoppers, deal seekers', '2023-11-15', '2023-11-30', 10000.00, 'completed', 134, 31, 18500.00, 85.00, 1),
('Email Newsletter Campaign', 'Monthly newsletter with hotel updates and offers', 'email', 'Newsletter subscribers', '2023-01-01', '2023-12-31', 2000.00, 'active', 156, 42, 4200.00, 110.00, 1),
('Social Media Engagement', 'Social media campaign to increase followers and engagement', 'social_media', 'Social media users, millennials', '2023-03-01', '2023-06-30', 4000.00, 'completed', 78, 19, 5800.00, 45.00, 1),
('Business Traveler Special', 'Special rates and packages for business travelers', 'promotion', 'Business professionals, corporate clients', '2023-07-01', '2023-12-31', 6000.00, 'active', 92, 25, 9500.00, 58.33, 1),
('Romantic Getaway Package', 'Valentine\'s Day and anniversary packages', 'seasonal', 'Couples, honeymooners', '2023-02-01', '2023-02-28', 3500.00, 'completed', 34, 8, 4800.00, 37.14, 1),
('Student Discount Program', 'Special rates for students and educational groups', 'promotion', 'Students, educational institutions', '2023-09-01', '2023-05-31', 2500.00, 'active', 45, 11, 3200.00, 28.00, 1),
('Luxury Experience Upgrade', 'Premium package upgrades and VIP services', 'loyalty', 'High-value customers, VIP members', '2023-04-01', '2023-12-31', 7000.00, 'active', 56, 15, 11200.00, 60.00, 1);

-- Insert promotional offers
INSERT INTO promotional_offers (code, name, description, offer_type, discount_value, discount_percentage, min_stay_nights, max_discount_amount, applicable_room_types, applicable_rate_plans, usage_limit, valid_from, valid_until) VALUES
('SUMMER15', 'Summer 15% Discount', '15% discount on all room types during summer', 'percentage_discount', NULL, 15.00, 2, 150.00, 'All', 'Standard,Deluxe,Suite', 500, '2023-06-01', '2023-08-31'),
('WINTER50', 'Winter Fixed Discount', '$50 off per night during winter season', 'fixed_amount_discount', 50.00, NULL, 3, NULL, 'Deluxe,Suite', 'Deluxe,Suite', 200, '2023-11-01', '2024-01-15'),
('LOYALTY10', 'Loyalty Member Discount', '10% discount for loyalty program members', 'percentage_discount', NULL, 10.00, 1, 100.00, 'All', 'All', NULL, '2023-01-01', '2023-12-31'),
('STUDENT20', 'Student Special Rate', '20% discount for students with valid ID', 'percentage_discount', NULL, 20.00, 1, 200.00, 'Single,Double', 'Standard', 100, '2023-09-01', '2023-05-31'),
('BUSINESS15', 'Business Traveler Discount', '15% discount for business travelers', 'percentage_discount', NULL, 15.00, 2, 120.00, 'Deluxe,Suite', 'Business', 300, '2023-01-01', '2023-12-31'),
('ROMANCE100', 'Romantic Package', '$100 off romantic getaway packages', 'fixed_amount_discount', 100.00, NULL, 2, NULL, 'Suite', 'Romance', 50, '2023-02-01', '2023-02-28'),
('VIPUPGRADE', 'VIP Room Upgrade', 'Free upgrade to next room category', 'upgrade', NULL, NULL, 3, NULL, 'Single,Double,Deluxe', 'VIP', 25, '2024-04-01', '2024-12-31'),
('FAMILYDEAL', 'Family Package Deal', 'Kids stay free with parents', 'package_deal', NULL, NULL, 2, NULL, 'Double,Deluxe,Suite', 'Family', 75, '2023-06-01', '2023-08-31');

-- Insert campaign performance data
INSERT INTO campaign_performance (campaign_id, performance_date, impressions, clicks, leads, conversions, revenue, spend) VALUES
(1, '2023-06-01', 15000, 1200, 45, 12, 8500.00, 5000.00),
(1, '2023-07-01', 18000, 1400, 38, 10, 7200.00, 4800.00),
(1, '2023-08-01', 12000, 900, 23, 6, 4200.00, 3200.00),
(2, '2023-11-01', 25000, 2000, 67, 18, 12000.00, 8000.00),
(2, '2023-12-01', 22000, 1800, 59, 16, 10800.00, 7200.00),
(3, '2023-09-01', 8000, 650, 89, 23, 6500.00, 3000.00),
(3, '2023-10-01', 7500, 600, 78, 20, 5800.00, 2800.00),
(3, '2023-11-01', 8200, 680, 85, 22, 6200.00, 3100.00),
(3, '2023-12-01', 7900, 640, 82, 21, 5900.00, 2950.00),
(4, '2023-11-15', 35000, 2800, 134, 31, 18500.00, 10000.00),
(4, '2023-11-20', 42000, 3200, 145, 35, 21000.00, 11500.00),
(4, '2023-11-25', 55000, 4200, 178, 42, 25200.00, 14000.00),
(4, '2023-11-30', 48000, 3600, 156, 38, 22800.00, 12500.00);

-- Update room statuses based on reservations and housekeeping
UPDATE rooms SET room_status = 'Occupied' WHERE id IN (2, 10, 12, 14, 16, 18, 22);
UPDATE rooms SET room_status = 'Reserved' WHERE id IN (5, 25);
UPDATE rooms SET room_status = 'Cleaning' WHERE id IN (4, 15, 25);
UPDATE rooms SET room_status = 'Maintenance' WHERE id IN (9, 20);

-- Update guest loyalty status based on business rules
UPDATE guests
SET loyalty_status = CASE
    WHEN (stay_count >= 5 OR total_spend >= 1000) THEN 'VIP'
    ELSE 'Regular'
END
WHERE stay_count >= 5 OR total_spend >= 1000;

SELECT 'Database reset and populated successfully!' AS Status;
