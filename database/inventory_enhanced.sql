USE hmscore1;

-- ITEMS TABLE (Base inventory items)
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    item_description TEXT,
    item_category VARCHAR(100),
    unit_of_measure VARCHAR(50) DEFAULT 'pcs',
    current_stock INT DEFAULT 0,
    minimum_stock INT DEFAULT 0,
    maximum_stock INT DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    supplier_id INT,
    item_status ENUM('Active', 'Inactive', 'Discontinued') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_item_name (item_name),
    INDEX idx_item_category (item_category),
    INDEX idx_item_status (item_status)
);

-- SUPPLIERS TABLE
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    payment_terms VARCHAR(100),
    supplier_status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_supplier_name (supplier_name),
    INDEX idx_supplier_status (supplier_status),
    INDEX idx_email (email)
);

-- INVENTORY MOVEMENTS TABLE
CREATE TABLE IF NOT EXISTS inventory_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT') NOT NULL,
    quantity INT NOT NULL,
    reason TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    reference_id VARCHAR(100), -- For linking to orders, sales, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_id (item_id),
    INDEX idx_movement_date (movement_date),
    INDEX idx_movement_type (movement_type),
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Add foreign key to items table for suppliers
ALTER TABLE items ADD CONSTRAINT fk_supplier_id FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- Sample Data for Suppliers
INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, city, state, postal_code, country, payment_terms, supplier_status) VALUES
('ABC Supplies Co.', 'Juan Dela Cruz', 'juan@abc-supplies.com', '+639123456789', '123 Industrial Ave', 'Manila', 'Metro Manila', '1000', 'Philippines', 'Net 30', 'Active'),
('Global Distributors Inc.', 'Maria Santos', 'maria@globaldist.com', '+639234567890', '456 Commerce Blvd', 'Makati', 'Metro Manila', '1200', 'Philippines', 'Net 15', 'Active'),
('Premium Goods Ltd.', 'Carlos Garcia', 'carlos@premiumgoods.ph', '+639345678901', '789 Quality St', 'Quezon City', 'Metro Manila', '1100', 'Philippines', 'Net 45', 'Active'),
('Local Traders Corp.', 'Elena Rodriguez', 'elena@localtraders.com', '+639456789012', '101 Market Rd', 'Cebu', 'Cebu', '6000', 'Philippines', 'Cash on Delivery', 'Active'),
('International Imports', 'Antonio Lopez', 'antonio@intimports.ph', '+639567890123', '202 Import Dr', 'Davao', 'Davao del Sur', '8000', 'Philippines', 'Net 60', 'Active');

-- Sample Data for Items
INSERT INTO items (item_name, item_description, item_category, unit_of_measure, current_stock, minimum_stock, maximum_stock, unit_cost, unit_price, supplier_id, item_status) VALUES
('Premium Towels', 'High-quality cotton bath towels', 'Linens', 'pcs', 150, 50, 300, 25.00, 45.00, 1, 'Active'),
('Bed Sheets (Queen)', 'Egyptian cotton queen size sheets', 'Linens', 'sets', 80, 20, 150, 35.00, 65.00, 1, 'Active'),
('Pillow Cases', 'Standard pillow cases', 'Linens', 'pcs', 200, 75, 400, 8.50, 15.00, 2, 'Active'),
('Toilet Paper', '2-ply premium toilet paper', 'Bathroom Supplies', 'rolls', 500, 100, 1000, 2.25, 4.50, 2, 'Active'),
('Shampoo Bottles', 'Travel-size shampoo bottles', 'Amenities', 'bottles', 300, 100, 600, 1.85, 3.50, 3, 'Active'),
('Coffee Pods', 'Premium coffee pods', 'Beverages', 'pods', 400, 150, 800, 0.65, 1.25, 3, 'Active'),
('Laundry Detergent', 'Professional laundry detergent', 'Cleaning Supplies', 'liters', 50, 20, 100, 18.50, 35.00, 4, 'Active'),
('Dish Soap', 'Concentrated dish washing liquid', 'Cleaning Supplies', 'liters', 75, 25, 150, 12.00, 22.00, 4, 'Active'),
('Light Bulbs', 'LED light bulbs 60W equivalent', 'Maintenance', 'pcs', 100, 30, 200, 12.00, 20.00, 5, 'Active'),
('Air Freshener', 'Room air freshener sprays', 'Amenities', 'cans', 120, 40, 250, 9.50, 18.00, 5, 'Active'),
('Hand Soap', 'Liquid hand soap dispensers', 'Bathroom Supplies', 'bottles', 180, 60, 350, 3.25, 6.50, 1, 'Active'),
('Tissue Boxes', 'Facial tissue boxes', 'Bathroom Supplies', 'boxes', 90, 30, 180, 3.50, 7.00, 2, 'Active'),
('Blankets', 'Thermal blankets', 'Linens', 'pcs', 60, 15, 120, 45.00, 85.00, 3, 'Active'),
('Conditioner Bottles', 'Travel-size conditioner', 'Amenities', 'bottles', 250, 80, 500, 1.95, 3.75, 3, 'Active'),
('Tea Bags', 'Assorted tea selection', 'Beverages', 'bags', 350, 125, 700, 0.35, 0.75, 4, 'Active'),
('Floor Cleaner', 'Multi-surface floor cleaner', 'Cleaning Supplies', 'liters', 40, 15, 80, 22.00, 40.00, 4, 'Active'),
('Extension Cords', 'Heavy-duty extension cords', 'Maintenance', 'pcs', 25, 10, 50, 25.00, 45.00, 5, 'Active'),
('Sugar Packets', 'Individual sugar packets', 'Beverages', 'packets', 800, 300, 1500, 0.15, 0.30, 5, 'Active'),
('Body Wash Bottles', 'Travel-size body wash', 'Amenities', 'bottles', 220, 70, 450, 2.10, 4.00, 1, 'Active'),
('Vacuum Bags', 'HEPA filter vacuum bags', 'Maintenance', 'packs', 35, 12, 70, 8.75, 16.00, 2, 'Active');

-- Sample Data for Inventory Movements
INSERT INTO inventory_movements (item_id, movement_type, quantity, reason, movement_date, user_id, reference_id) VALUES
(1, 'IN', 100, 'Initial stock purchase', '2024-01-01 09:00:00', 1, 'PO-2024-001'),
(2, 'IN', 50, 'Restock order', '2024-01-05 10:30:00', 1, 'PO-2024-002'),
(3, 'IN', 150, 'Bulk purchase', '2024-01-10 14:15:00', 1, 'PO-2024-003'),
(4, 'IN', 300, 'Monthly supply order', '2024-01-15 11:45:00', 1, 'PO-2024-004'),
(5, 'IN', 200, 'Amenities restock', '2024-01-20 16:20:00', 1, 'PO-2024-005'),
(6, 'IN', 250, 'Beverage supplies', '2024-01-25 13:10:00', 1, 'PO-2024-006'),
(7, 'IN', 30, 'Cleaning supplies order', '2024-02-01 09:30:00', 1, 'PO-2024-007'),
(8, 'IN', 45, 'Kitchen supplies', '2024-02-05 15:45:00', 1, 'PO-2024-008'),
(9, 'IN', 75, 'Maintenance items', '2024-02-10 12:00:00', 1, 'PO-2024-009'),
(10, 'IN', 80, 'Room amenities', '2024-02-15 10:15:00', 1, 'PO-2024-010'),
(1, 'OUT', 25, 'Used in housekeeping', '2024-02-20 08:30:00', 2, 'HK-2024-001'),
(2, 'OUT', 10, 'Room preparation', '2024-02-21 09:45:00', 2, 'HK-2024-002'),
(3, 'OUT', 30, 'Guest room setup', '2024-02-22 14:20:00', 2, 'HK-2024-003'),
(4, 'OUT', 50, 'Bathroom restocking', '2024-02-23 16:00:00', 2, 'HK-2024-004'),
(5, 'OUT', 40, 'Guest amenities', '2024-02-24 11:30:00', 2, 'HK-2024-005'),
(6, 'OUT', 60, 'Coffee station', '2024-02-25 07:15:00', 2, 'HK-2024-006'),
(7, 'OUT', 5, 'Laundry operations', '2024-02-26 13:45:00', 2, 'HK-2024-007'),
(8, 'OUT', 8, 'Kitchen cleaning', '2024-02-27 10:00:00', 2, 'HK-2024-008'),
(9, 'OUT', 15, 'Room maintenance', '2024-02-28 15:30:00', 2, 'HK-2024-009'),
(10, 'OUT', 20, 'Air freshening', '2024-03-01 12:15:00', 2, 'HK-2024-010'),
(11, 'IN', 100, 'Monthly hand soap order', '2024-03-05 14:00:00', 1, 'PO-2024-011'),
(12, 'IN', 60, 'Tissue supplies', '2024-03-10 11:20:00', 1, 'PO-2024-012'),
(13, 'IN', 25, 'Blanket purchase', '2024-03-15 09:45:00', 1, 'PO-2024-013'),
(14, 'IN', 150, 'Conditioner restock', '2024-03-20 16:30:00', 1, 'PO-2024-014'),
(15, 'IN', 200, 'Tea supplies', '2024-03-25 13:00:00', 1, 'PO-2024-015'),
(16, 'IN', 20, 'Floor cleaning supplies', '2024-03-30 10:45:00', 1, 'PO-2024-016'),
(17, 'IN', 15, 'Electrical supplies', '2024-04-05 15:15:00', 1, 'PO-2024-017'),
(18, 'IN', 500, 'Sugar packets bulk', '2024-04-10 12:30:00', 1, 'PO-2024-018'),
(19, 'IN', 120, 'Body wash supplies', '2024-04-15 09:00:00', 1, 'PO-2024-019'),
(20, 'IN', 20, 'Vacuum supplies', '2024-04-20 14:45:00', 1, 'PO-2024-020'),
(11, 'OUT', 35, 'Bathroom restocking', '2024-04-25 08:30:00', 2, 'HK-2024-011'),
(12, 'OUT', 12, 'Guest rooms', '2024-04-26 11:15:00', 2, 'HK-2024-012'),
(13, 'OUT', 8, 'Cold weather preparation', '2024-04-27 16:20:00', 2, 'HK-2024-013'),
(14, 'OUT', 45, 'Guest amenities', '2024-04-28 13:45:00', 2, 'HK-2024-014'),
(15, 'OUT', 75, 'Beverage station', '2024-04-29 10:00:00', 2, 'HK-2024-015'),
(16, 'OUT', 6, 'Floor maintenance', '2024-04-30 15:30:00', 2, 'HK-2024-016'),
(17, 'OUT', 3, 'Electrical repairs', '2024-05-01 12:15:00', 2, 'HK-2024-017'),
(18, 'OUT', 120, 'Coffee station supplies', '2024-05-02 09:45:00', 2, 'HK-2024-018'),
(19, 'OUT', 30, 'Bathroom amenities', '2024-05-03 14:20:00', 2, 'HK-2024-019'),
(20, 'OUT', 8, 'Cleaning equipment', '2024-05-04 11:00:00', 2, 'HK-2024-020');
