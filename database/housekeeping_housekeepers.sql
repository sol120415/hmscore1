USE hmscore1;

-- =============================================
-- HOUSEKEEPING TABLES (INTEGRATED WITH ROOMS)
-- =============================================

-- Housekeepers table (staff management)
CREATE TABLE IF NOT EXISTS housekeepers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(15),
    email VARCHAR(100),
    hire_date DATE NOT NULL,
    status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
    specialty VARCHAR(100), -- e.g., 'Deep Cleaning', 'Maintenance', 'General'
    shift_preference ENUM('Morning', 'Afternoon', 'Evening', 'Night', 'Flexible') DEFAULT 'Flexible',
    max_rooms_per_day INT DEFAULT 10,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_shift_preference (shift_preference),
    INDEX idx_specialty (specialty)
);

-- Housekeeping tasks table (room cleaning assignments) - INTEGRATED WITH ROOMS
CREATE TABLE IF NOT EXISTS housekeeping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL, -- Reference to rooms table
    housekeeper_id INT,
    task_type ENUM('Regular Cleaning', 'Deep Cleaning', 'Maintenance', 'Inspection', 'Emergency') DEFAULT 'Regular Cleaning',
    priority ENUM('Low', 'Normal', 'High', 'Urgent') DEFAULT 'Normal',
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled', 'Skipped') DEFAULT 'Pending',
    scheduled_date DATE NOT NULL,
    scheduled_time TIME,
    actual_start_time DATETIME,
    actual_end_time DATETIME,
    estimated_duration_minutes INT DEFAULT 60,
    actual_duration_minutes INT,
    cleaning_supplies_used TEXT, -- JSON array of supplies used
    issues_found TEXT, -- Description of any issues discovered
    maintenance_required BOOLEAN DEFAULT FALSE,
    guest_feedback TEXT,
    supervisor_notes TEXT,
    created_by INT, -- User ID who created the task
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (housekeeper_id) REFERENCES housekeepers(id) ON DELETE SET NULL,
    INDEX idx_room_id (room_id),
    INDEX idx_housekeeper_id (housekeeper_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_priority (priority),
    INDEX idx_task_type (task_type),
    INDEX idx_maintenance_required (maintenance_required)
);

-- Housekeeping supplies inventory
CREATE TABLE IF NOT EXISTS housekeeping_supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_name VARCHAR(100) NOT NULL,
    category ENUM('Cleaning', 'Maintenance', 'Linens', 'Amenities', 'Equipment') NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    unit_of_measure ENUM('pieces', 'liters', 'kg', 'boxes', 'sets') DEFAULT 'pieces',
    minimum_stock_level DECIMAL(10,2) DEFAULT 10,
    supplier VARCHAR(100),
    last_restock_date DATE,
    cost_per_unit DECIMAL(8,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category),
    INDEX idx_supply_name (supply_name),
    INDEX idx_minimum_stock (minimum_stock_level)
);


-- =============================================
-- INVENTORY SUPPLIES SAMPLE DATA (ENHANCED)
-- =============================================

-- Insert comprehensive sample inventory supplies
INSERT INTO housekeeping_supplies (supply_name, category, current_stock, unit_of_measure, minimum_stock_level, supplier, cost_per_unit, notes) VALUES
-- Cleaning Supplies
('All-Purpose Cleaner', 'Cleaning', 25.5, 'liters', 10, 'CleanCo Supplies', 15.50, 'Multi-surface cleaner for general use'),
('Glass Cleaner', 'Cleaning', 15.2, 'liters', 8, 'CleanCo Supplies', 12.75, 'Streak-free glass and mirror cleaner'),
('Disinfectant Spray', 'Cleaning', 12.8, 'liters', 5, 'CleanCo Supplies', 18.90, 'Hospital-grade disinfectant'),
('Toilet Bowl Cleaner', 'Cleaning', 18.0, 'liters', 6, 'CleanCo Supplies', 14.25, 'Concentrated toilet cleaner'),
('Floor Cleaner', 'Cleaning', 8.5, 'liters', 12, 'CleanCo Supplies', 22.00, 'Heavy-duty floor cleaning solution'),
('Carpet Shampoo', 'Cleaning', 6.2, 'liters', 4, 'CleanCo Supplies', 28.50, 'Professional carpet cleaning solution'),

-- Maintenance Supplies
('Vacuum Cleaner Bags', 'Equipment', 30, 'pieces', 10, 'Maintenance Plus', 8.75, 'HEPA filter replacement bags'),
('Light Bulbs (LED)', 'Equipment', 45, 'pieces', 20, 'Maintenance Plus', 12.00, 'Energy-efficient LED bulbs'),
('Air Freshener Refills', 'Equipment', 22, 'pieces', 15, 'Maintenance Plus', 9.50, 'Room freshener cartridges'),
('Batteries (AA)', 'Equipment', 80, 'pieces', 50, 'Maintenance Plus', 0.85, 'Alkaline batteries for remotes'),
('Extension Cords', 'Equipment', 12, 'pieces', 5, 'Maintenance Plus', 25.00, 'Heavy-duty extension cords'),

-- Linens
('Bed Sheets (Queen)', 'Linens', 45, 'pieces', 20, 'Linen World', 35.00, 'High-thread count cotton sheets'),
('Pillow Cases', 'Linens', 60, 'pieces', 25, 'Linen World', 8.50, 'Standard pillow cases'),
('Towels (Bath)', 'Linens', 80, 'pieces', 30, 'Linen World', 12.75, 'Absorbent cotton bath towels'),
('Towels (Hand)', 'Linens', 95, 'pieces', 40, 'Linen World', 6.25, 'Small hand towels for bathrooms'),
('Towels (Face)', 'Linens', 110, 'pieces', 45, 'Linen World', 4.50, 'Soft facial towels'),
('Blankets', 'Linens', 25, 'pieces', 10, 'Linen World', 45.00, 'Thermal blankets for cold weather'),

-- Amenities
('Toilet Paper', 'Amenities', 200, 'pieces', 50, 'Hotel Essentials', 2.25, 'Premium 2-ply toilet paper'),
('Tissue Boxes', 'Amenities', 75, 'pieces', 30, 'Hotel Essentials', 3.50, 'Facial tissue boxes'),
('Shampoo (Individual)', 'Amenities', 180, 'pieces', 60, 'Hotel Essentials', 1.85, 'Travel-size shampoo bottles'),
('Conditioner (Individual)', 'Amenities', 165, 'pieces', 60, 'Hotel Essentials', 1.95, 'Travel-size conditioner'),
('Body Wash (Individual)', 'Amenities', 155, 'pieces', 60, 'Hotel Essentials', 2.10, 'Travel-size body wash'),
('Lotion (Individual)', 'Amenities', 140, 'pieces', 50, 'Hotel Essentials', 2.25, 'Travel-size hand lotion'),

-- Equipment
('Coffee Pods (Regular)', 'Equipment', 85, 'pieces', 40, 'Beverage Distributors', 0.65, 'Premium coffee pods'),
('Coffee Pods (Decaf)', 'Equipment', 55, 'pieces', 25, 'Beverage Distributors', 0.70, 'Decaffeinated coffee pods'),
('Tea Bags', 'Equipment', 120, 'pieces', 50, 'Beverage Distributors', 0.35, 'Assorted tea bag selection'),
('Sugar Packets', 'Equipment', 300, 'pieces', 100, 'Beverage Distributors', 0.15, 'Individual sugar packets'),
('Coffee Creamer', 'Equipment', 45, 'pieces', 20, 'Beverage Distributors', 0.85, 'Individual creamer cups'),

-- Low stock items for testing alerts
('Hand Sanitizer', 'Cleaning', 3.2, 'liters', 8, 'CleanCo Supplies', 22.00, 'Alcohol-based hand sanitizer'),
('Laundry Detergent', 'Cleaning', 2.5, 'kg', 10, 'CleanCo Supplies', 18.50, 'Professional laundry detergent'),
('Out of Stock Item', 'Equipment', 0, 'pieces', 5, 'Test Supplier', 50.00, 'This item is out of stock for testing');

-- Insert sample housekeepers
INSERT INTO housekeepers (first_name, last_name, employee_id, phone, email, hire_date, status, specialty, shift_preference, max_rooms_per_day, notes) VALUES
('Maria', 'Rodriguez', 'HK001', '+639123456789', 'maria.rodriguez@hotel.com', '2023-01-15', 'Active', 'General Cleaning', 'Morning', 12, 'Experienced housekeeper with 5 years of service'),
('Carlos', 'Santos', 'HK002', '+639234567890', 'carlos.santos@hotel.com', '2023-03-20', 'Active', 'Deep Cleaning', 'Afternoon', 10, 'Specializes in deep cleaning and maintenance'),
('Elena', 'Garcia', 'HK003', '+639345678901', 'elena.garcia@hotel.com', '2023-05-10', 'Active', 'Maintenance', 'Flexible', 8, 'Handles maintenance tasks and repairs'),
('Antonio', 'Lopez', 'HK004', '+639456789012', 'antonio.lopez@hotel.com', '2023-07-05', 'Active', 'General Cleaning', 'Evening', 12, 'Reliable evening shift housekeeper'),
('Rosa', 'Martinez', 'HK005', '+639567890123', 'rosa.martinez@hotel.com', '2023-09-12', 'Active', 'Inspection', 'Morning', 15, 'Focuses on room inspections and quality control');

-- Insert sample housekeeping tasks (UPDATED to use actual room IDs)
INSERT INTO housekeeping (room_id, housekeeper_id, task_type, priority, scheduled_date, scheduled_time, estimated_duration_minutes, created_by) VALUES
(3, 1, 'Regular Cleaning', 'Normal', CURDATE(), '09:00:00', 45, 1),  -- Room 103 (Cleaning status)
(9, 2, 'Deep Cleaning', 'High', CURDATE(), '10:30:00', 90, 1),     -- Room 203 (Cleaning status)
(14, 3, 'Maintenance', 'Urgent', CURDATE(), '14:00:00', 120, 1),   -- Room 304 (Maintenance status)
(19, 4, 'Inspection', 'Normal', CURDATE(), '16:00:00', 30, 1),    -- Room 404 (Maintenance status)
(1, 5, 'Regular Cleaning', 'Normal', CURDATE(), '09:30:00', 45, 1), -- Room 101 (Vacant status)
(6, 1, 'Regular Cleaning', 'Normal', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 45, 1),  -- Room 201 (Vacant status)
(11, 2, 'Regular Cleaning', 'Normal', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 50, 1), -- Room 301 (Vacant status)
(24, 3, 'Maintenance', 'High', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00', 60, 1);        -- Room 504 (Maintenance status)
