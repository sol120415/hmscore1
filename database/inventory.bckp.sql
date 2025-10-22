-- Inventory schema extracted from backup export.sql
-- Target database
USE hmscore1;

-- --------------------------------------------------------
-- Table: inventory_categories
CREATE TABLE IF NOT EXISTS inventory_categories (
  id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  description text DEFAULT NULL,
  parent_category_id int(11) DEFAULT NULL,
  is_active tinyint(1) DEFAULT 1,
  created_by int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional)
INSERT INTO inventory_categories (id, name, description, parent_category_id, is_active, created_by, created_at, updated_at) VALUES
  (1, 'Food & Beverages', 'Food items, beverages, and related products', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (2, 'Cleaning Supplies', 'Cleaning products and maintenance supplies', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (3, 'Linens & Towels', 'Bed linens, towels, and related textile items', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (4, 'Room Amenities', 'Guest room supplies and amenities', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (5, 'Office Supplies', 'Office and administrative supplies', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (6, 'Electronics', 'Electronic equipment and accessories', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (7, 'Furniture', 'Furniture and fixtures', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (8, 'Kitchen Equipment', 'Kitchen appliances and equipment', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (9, 'Maintenance', 'Maintenance and repair supplies', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (10, 'Miscellaneous', 'Other inventory items not categorized elsewhere', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- --------------------------------------------------------
-- Table: inventory_items
CREATE TABLE IF NOT EXISTS inventory_items (
  id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  description text DEFAULT NULL,
  category_id int(11) NOT NULL,
  supplier_id int(11) DEFAULT NULL,
  sku varchar(100) DEFAULT NULL,
  barcode varchar(100) DEFAULT NULL,
  unit_of_measure enum('pieces','kg','liters','boxes','packets','bottles','sets') DEFAULT 'pieces',
  minimum_stock_level int(11) DEFAULT 0,
  maximum_stock_level int(11) DEFAULT NULL,
  reorder_point int(11) DEFAULT 0,
  unit_cost decimal(10,2) DEFAULT 0.00,
  selling_price decimal(10,2) DEFAULT NULL,
  location varchar(255) DEFAULT NULL,
  is_perishable tinyint(1) DEFAULT 0,
  expiry_date date DEFAULT NULL,
  is_active tinyint(1) DEFAULT 1,
  created_by int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional)
INSERT INTO inventory_items (id, name, description, category_id, supplier_id, sku, barcode, unit_of_measure, minimum_stock_level, maximum_stock_level, reorder_point, unit_cost, selling_price, location, is_perishable, expiry_date, is_active, created_by, created_at, updated_at) VALUES
  (1, 'White Bath Towels', 'Standard white bath towels for guest rooms', 3, 3, 'TOWEL-WHITE-001', NULL, 'pieces', 50, 200, 75, 8.50, 12.75, 'Linen Storage Room A', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40'),
  (2, 'Shampoo Bottles', 'Individual shampoo bottles for guest rooms', 4, 4, 'SHAMPOO-001', NULL, 'bottles', 100, 500, 150, 1.25, 1.88, 'Amenities Storage B', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40'),
  (3, 'Coffee Packets', 'Individual coffee packets for guest rooms', 1, 1, 'COFFEE-001', NULL, 'packets', 200, 1000, 300, 0.75, 1.13, 'Kitchen Storage C', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40'),
  (4, 'All-Purpose Cleaner', 'Multi-surface cleaning solution', 2, 2, 'CLEANER-ALL-001', NULL, 'bottles', 20, 100, 30, 12.50, 18.75, 'Maintenance Storage D', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- --------------------------------------------------------
-- Table: inventory_stock
CREATE TABLE IF NOT EXISTS inventory_stock (
  id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  current_stock int(11) NOT NULL DEFAULT 0,
  reserved_stock int(11) NOT NULL DEFAULT 0,
  available_stock int(11) NOT NULL DEFAULT 0,
  last_updated timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  updated_by int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional)
INSERT INTO inventory_stock (id, item_id, current_stock, reserved_stock, available_stock, last_updated, updated_by) VALUES
  (1, 1, 150, 0, 150, '2025-10-16 05:43:23', NULL),
  (2, 2, 300, 0, 300, '2025-10-16 05:43:23', NULL),
  (3, 3, 500, 0, 500, '2025-10-16 05:43:23', NULL),
  (4, 4, 50, 0, 50, '2025-10-16 05:43:23', NULL)
ON DUPLICATE KEY UPDATE current_stock=VALUES(current_stock), reserved_stock=VALUES(reserved_stock), available_stock=VALUES(available_stock);

-- Trigger: update_available_stock
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_available_stock BEFORE UPDATE ON inventory_stock FOR EACH ROW BEGIN
    SET NEW.available_stock = NEW.current_stock - NEW.reserved_stock;
END $$
DELIMITER ;

-- --------------------------------------------------------
-- Table: inventory_suppliers
CREATE TABLE IF NOT EXISTS inventory_suppliers (
  id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  contact_person varchar(255) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  phone varchar(50) DEFAULT NULL,
  address text DEFAULT NULL,
  city varchar(100) DEFAULT NULL,
  state varchar(100) DEFAULT NULL,
  zip_code varchar(20) DEFAULT NULL,
  country varchar(100) DEFAULT NULL,
  website varchar(255) DEFAULT NULL,
  notes text DEFAULT NULL,
  is_active tinyint(1) DEFAULT 1,
  created_by int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional)
INSERT INTO inventory_suppliers (id, name, contact_person, email, phone, address, city, state, zip_code, country, website, notes, is_active, created_by, created_at, updated_at) VALUES
  (1, 'Fresh Foods Supplier', 'John Smith', 'john@freshfoods.com', '+1-555-0123', '123 Market Street', 'Springfield', 'IL', '62701', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (2, 'Cleaning Supplies Co', 'Sarah Johnson', 'sarah@cleaningsupplies.com', '+1-555-0124', '456 Industrial Blvd', 'Springfield', 'IL', '62702', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (3, 'Linen & Textile Ltd', 'Mike Davis', 'mike@linentextile.com', '+1-555-0125', '789 Textile Ave', 'Springfield', 'IL', '62703', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (4, 'Hotel Amenities Inc', 'Lisa Wilson', 'lisa@hotelamenities.com', '+1-555-0126', '321 Guest Lane', 'Springfield', 'IL', '62704', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
  (5, 'joseph lopez', 'dfghjkl', 'josephlopez102004@gmail.com', '09777456465', 'ninang vir', 'Caloocan City', 'phil', '1414', 'Philippines', '', '', 1, 4, '2025-10-16 06:19:59', '2025-10-16 06:19:59')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- --------------------------------------------------------
-- Table: inventory_purchase_orders
CREATE TABLE IF NOT EXISTS inventory_purchase_orders (
  id int(11) NOT NULL,
  po_number varchar(50) NOT NULL,
  supplier_id int(11) NOT NULL,
  order_date date NOT NULL,
  expected_delivery_date date DEFAULT NULL,
  status enum('draft','sent','confirmed','delivered','cancelled') DEFAULT 'draft',
  total_amount decimal(10,2) DEFAULT 0.00,
  notes text DEFAULT NULL,
  created_by int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: inventory_purchase_order_items
CREATE TABLE IF NOT EXISTS inventory_purchase_order_items (
  id int(11) NOT NULL,
  purchase_order_id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  quantity int(11) NOT NULL,
  unit_cost decimal(10,2) NOT NULL,
  total_cost decimal(10,2) NOT NULL,
  received_quantity int(11) DEFAULT 0,
  notes text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: inventory_stock_history
CREATE TABLE IF NOT EXISTS inventory_stock_history (
  id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  operation_type enum('stock_in','stock_out','adjustment','reservation','release','expiry','damage') NOT NULL,
  quantity int(11) NOT NULL,
  previous_stock int(11) NOT NULL,
  new_stock int(11) NOT NULL,
  reference_id varchar(100) DEFAULT NULL,
  reference_type enum('purchase_order','sale','adjustment','reservation','transfer','expiry','damage') DEFAULT NULL,
  unit_cost decimal(10,2) DEFAULT NULL,
  total_cost decimal(10,2) DEFAULT NULL,
  notes text DEFAULT NULL,
  performed_by int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Views
DROP VIEW IF EXISTS inventory_category_summary;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW inventory_category_summary AS
SELECT c.id AS category_id,
       c.name AS category_name,
       COUNT(i.id) AS total_items,
       SUM(s.current_stock) AS total_stock,
       SUM(s.current_stock * i.unit_cost) AS total_value,
       COUNT(CASE WHEN s.current_stock <= i.minimum_stock_level THEN 1 END) AS low_stock_items
FROM (inventory_categories c
      LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1)
      LEFT JOIN inventory_stock s ON i.id = s.item_id
WHERE c.is_active = 1
GROUP BY c.id, c.name;

DROP VIEW IF EXISTS inventory_low_stock_alerts;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW inventory_low_stock_alerts AS
SELECT i.id AS id,
       i.name AS name,
       i.sku AS sku,
       i.minimum_stock_level AS minimum_stock_level,
       s.current_stock AS current_stock,
       s.available_stock AS available_stock,
       c.name AS category_name,
       i.location AS location
FROM inventory_items i
JOIN inventory_stock s ON i.id = s.item_id
JOIN inventory_categories c ON i.category_id = c.id
WHERE i.is_active = 1 AND s.current_stock <= i.minimum_stock_level
ORDER BY s.current_stock ASC;

-- --------------------------------------------------------
-- Indexes and AUTO_INCREMENT
ALTER TABLE inventory_categories
  ADD PRIMARY KEY (id),
  ADD KEY idx_name (name),
  ADD KEY idx_parent_category (parent_category_id),
  ADD KEY idx_is_active (is_active),
  ADD KEY idx_created_by (created_by);

ALTER TABLE inventory_items
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY sku (sku),
  ADD UNIQUE KEY barcode (barcode),
  ADD KEY idx_name (name),
  ADD KEY idx_category (category_id),
  ADD KEY idx_supplier (supplier_id),
  ADD KEY idx_unit_of_measure (unit_of_measure),
  ADD KEY idx_location (location),
  ADD KEY idx_is_perishable (is_perishable),
  ADD KEY idx_expiry_date (expiry_date),
  ADD KEY idx_is_active (is_active),
  ADD KEY idx_created_by (created_by);

ALTER TABLE inventory_purchase_orders
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY po_number (po_number),
  ADD KEY idx_supplier (supplier_id),
  ADD KEY idx_order_date (order_date),
  ADD KEY idx_expected_delivery (expected_delivery_date),
  ADD KEY idx_status (status),
  ADD KEY idx_created_by (created_by);

ALTER TABLE inventory_purchase_order_items
  ADD PRIMARY KEY (id),
  ADD KEY idx_purchase_order (purchase_order_id),
  ADD KEY idx_item_id (item_id),
  ADD KEY idx_received_quantity (received_quantity);

ALTER TABLE inventory_stock
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY unique_item_stock (item_id),
  ADD KEY idx_item_id (item_id),
  ADD KEY idx_current_stock (current_stock),
  ADD KEY idx_available_stock (available_stock),
  ADD KEY idx_last_updated (last_updated),
  ADD KEY idx_updated_by (updated_by);

ALTER TABLE inventory_stock_history
  ADD PRIMARY KEY (id),
  ADD KEY idx_item_id (item_id),
  ADD KEY idx_operation_type (operation_type),
  ADD KEY idx_reference_id (reference_id),
  ADD KEY idx_reference_type (reference_type),
  ADD KEY idx_performed_by (performed_by),
  ADD KEY idx_created_at (created_at);

ALTER TABLE inventory_suppliers
  ADD PRIMARY KEY (id),
  ADD KEY idx_name (name),
  ADD KEY idx_email (email),
  ADD KEY idx_phone (phone),
  ADD KEY idx_is_active (is_active),
  ADD KEY idx_created_by (created_by);

-- AUTO_INCREMENT
ALTER TABLE inventory_categories MODIFY id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE inventory_items MODIFY id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE inventory_purchase_orders MODIFY id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE inventory_purchase_order_items MODIFY id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE inventory_stock MODIFY id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE inventory_stock_history MODIFY id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE inventory_suppliers MODIFY id int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Foreign Keys (will succeed if referenced tables exist)
ALTER TABLE inventory_items
  ADD CONSTRAINT inventory_items_fk_category FOREIGN KEY (category_id) REFERENCES inventory_categories(id),
  ADD CONSTRAINT inventory_items_fk_supplier FOREIGN KEY (supplier_id) REFERENCES inventory_suppliers(id) ON DELETE SET NULL;

ALTER TABLE inventory_purchase_orders
  ADD CONSTRAINT inventory_po_fk_supplier FOREIGN KEY (supplier_id) REFERENCES inventory_suppliers(id) ON DELETE CASCADE;

ALTER TABLE inventory_purchase_order_items
  ADD CONSTRAINT inventory_poi_fk_po FOREIGN KEY (purchase_order_id) REFERENCES inventory_purchase_orders(id) ON DELETE CASCADE,
  ADD CONSTRAINT inventory_poi_fk_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE;

ALTER TABLE inventory_stock
  ADD CONSTRAINT inventory_stock_fk_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE;

ALTER TABLE inventory_stock_history
  ADD CONSTRAINT inventory_stock_history_fk_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE;


