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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
