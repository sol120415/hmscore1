 USE hmscore1;
-- GUESTS TABLE (Guest Information Management)
CREATE TABLE IF NOT EXISTS guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    id_type ENUM('Passport', 'Driver License', 'National ID') DEFAULT 'National ID' NOT NULL,
    id_number VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    nationality VARCHAR(100) NULL,
    notes TEXT NULL,
    guest_status ENUM('Active', 'Archived') DEFAULT 'Active',
    loyalty_status ENUM('Regular', 'Iron', 'Gold', 'Diamond') DEFAULT 'Regular',
    stay_count INT DEFAULT 0,
    total_spend DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_last_name (last_name),
    INDEX idx_guest_status (guest_status),
    INDEX idx_created_at (created_at)
);

-- Sample Data
INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, guest_status, loyalty_status, notes) VALUES
('Juan', 'Dela Cruz', 'juan.delacruz@example.com', '+639123456789', '123 Rizal St', 'Manila', 'Philippines', 'National ID', 'PH123456789', '1990-01-15', 'Filipino', 'Active', 'Regular', 'Sample guest data'),
('Maria', 'Santos', 'maria.santos@example.com', '+639234567890', '456 Bonifacio Ave', 'Quezon City', 'Philippines', 'Driver License', 'DL987654321', '1985-05-20', 'Filipino', 'Active', 'Iron', 'Sample guest data'),
('Jose', 'Reyes', 'jose.reyes@example.com', '+639345678901', '789 Mabini Rd', 'Cebu', 'Philippines', 'Passport', 'PP456789123', '1978-10-10', 'Filipino', 'Active', 'Gold', 'Sample guest data'),
('Ana', 'Garcia', 'ana.garcia@example.com', '+639456789012', '101 Aguinaldo Blvd', 'Davao', 'Philippines', 'National ID', 'PH234567890', '1992-08-05', 'Filipino', 'Active', 'Diamond', 'Sample guest data'),
('Pedro', 'Lopez', 'pedro.lopez@example.com', '+639567890123', '202 Osmena St', 'Manila', 'Philippines', 'Driver License', 'DL876543210', '1988-03-25', 'Filipino', 'Active', 'Regular', 'Sample guest data'),
('Rosa', 'Martinez', 'rosa.martinez@example.com', '+639678901234', '303 Roxas Ave', 'Makati', 'Philippines', 'Passport', 'PP567890234', '1995-06-30', 'Filipino', 'Active', 'Iron', 'Sample guest data'),
('Miguel', 'Torres', 'miguel.torres@example.com', '+639789012345', '404 Quezon Blvd', 'Pasig', 'Philippines', 'National ID', 'PH345678901', '1982-11-12', 'Filipino', 'Active', 'Gold', 'Sample guest data'),
('Carmen', 'Flores', 'carmen.flores@example.com', '+639890123456', '505 Laurel St', 'Taguig', 'Philippines', 'Driver License', 'DL765432109', '1990-02-14', 'Filipino', 'Active', 'Diamond', 'Sample guest data'),
('Antonio', 'Ramirez', 'antonio.ramirez@example.com', '+639901234567', '606 Magsaysay Ave', 'Manila', 'Philippines', 'Passport', 'PP678901345', '1987-07-08', 'Filipino', 'Active', 'Regular', 'Sample guest data'),
('Elena', 'Castillo', 'elena.castillo@example.com', '+639012345678', '707 Garcia St', 'Baguio', 'Philippines', 'National ID', 'PH456789012', '1993-04-22', 'Filipino', 'Active', 'Iron', 'Sample guest data'),
('John', 'Smith', 'john.smith@example.com', '+1-555-123-4567', '123 Main St', 'New York', 'USA', 'Passport', 'US123456789', '1980-12-01', 'American', 'Active', 'Gold', 'Sample guest data'),
('Emma', 'Johnson', 'emma.johnson@example.com', '+44-20-7946-0958', '456 High St', 'London', 'UK', 'Driver License', 'UK987654321', '1991-09-15', 'British', 'Active', 'Diamond', 'Sample guest data'),
('Liam', 'Brown', 'liam.brown@example.com', '+1-416-555-7890', '789 Maple Ave', 'Toronto', 'Canada', 'National ID', 'CA456789123', '1986-04-10', 'Canadian', 'Active', 'Regular', 'Sample guest data'),
('Sophia', 'Davis', 'sophia.davis@example.com', '+61-2-9374-4000', '101 Sydney St', 'Sydney', 'Australia', 'Passport', 'AU567890234', '1994-01-28', 'Australian', 'Active', 'Iron', 'Sample guest data'),
('Noah', 'Wilson', 'noah.wilson@example.com', '+49-30-12345678', '202 Berlin Rd', 'Berlin', 'Germany', 'Driver License', 'DE678901345', '1989-11-05', 'German', 'Active', 'Gold', 'Sample guest data');
