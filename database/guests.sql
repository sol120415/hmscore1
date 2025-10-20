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
    loyalty_status ENUM('Regular', 'VIP') DEFAULT 'Regular',
    stay_count INT DEFAULT 0,
    total_spend DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_last_name (last_name),
    INDEX idx_created_at (created_at)
);

-- Sample Data
INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, notes) VALUES
('John', 'Doe', 'john.doe@example.com', '1234567890', '123 Main St', 'Anytown', 'USA', 'Passport', 'AB123456', '1990-01-01', 'American', 'Sample guest data'),
('Jane', 'Smith', 'jane.smith@example.com', '0987654321', '456 Elm St', 'Othertown', 'Canada', 'Driver License', 'CD789012', '1985-05-15', 'Canadian', 'Sample guest data'),
('Bob', 'Johnson', 'bob.johnson@example.com', '1122334455', '789 Oak St', 'Thirdtown', 'UK', 'National ID', 'ID123456', '1978-10-20', 'British', 'Sample guest data'),
('Alice', 'Williams', 'alice.williams@example.com', '2233445566', '101 Pine St', 'Fourthtown', 'Australia', 'Passport', 'AE123456', '1992-08-25', 'Australian', 'Sample guest data'),
('David', 'Brown', 'david.brown@example.com', '3344556677', '202 Maple St', 'Fifthtown', 'Germany', 'Driver License', 'DB123456', '1988-03-10', 'German', 'Sample guest data'),
('Emily', 'Davis', 'emily.davis@example.com', '4455667788', '303 Birch St', 'Sixthtown', 'France', 'Passport', 'FD123456', '1995-06-12', 'French', 'Sample guest data'),
('Michael', 'Wilson', 'michael.wilson@example.com', '5566778899', '404 Willow St', 'Seventhtown', 'Italy', 'Driver License', 'DW123456', '1982-11-25', 'Italian', 'Sample guest data'),
('Sarah', 'Taylor', 'sarah.taylor@example.com', '6677889900', '505 Cedar St', 'Eighthtown', 'Spain', 'Passport', 'ST123456', '1990-02-18', 'Spanish', 'Sample guest data'),
('William', 'Anderson', 'william.anderson@example.com', '7788990011', '606 Pine St', 'Ninethtown', 'Japan', 'Driver License', 'WA123456', '1987-07-22', 'Japanese', 'Sample guest data'),
('Olivia', 'Green', 'olivia.green@example.com', '8899001122', '707 Oak St', 'Tenth town', 'China', 'Passport', 'OG123456', '1993-04-28', 'Chinese', 'Sample guest data');
