USE hmscore1;

-- MARKETING CAMPAIGNS TABLE
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    campaign_type ENUM('email', 'social_media', 'advertising', 'promotion', 'loyalty', 'seasonal') NOT NULL,
    target_audience TEXT,
    start_date DATE NOT NULL,
    end_date DATE,
    budget DECIMAL(10,2),
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    leads_generated INT DEFAULT 0,
    conversions INT DEFAULT 0,
    revenue_generated DECIMAL(10,2) DEFAULT 0,
    roi_percentage DECIMAL(5,2),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_campaign_type (campaign_type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    INDEX idx_created_by (created_by)
);

-- PROMOTIONAL OFFERS TABLE
CREATE TABLE IF NOT EXISTS promotional_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    offer_type ENUM('percentage_discount', 'fixed_amount_discount', 'free_nights', 'upgrade', 'package_deal') NOT NULL,
    discount_value DECIMAL(10,2),
    discount_percentage DECIMAL(5,2),
    min_stay_nights INT DEFAULT 1,
    max_discount_amount DECIMAL(10,2),
    applicable_room_types TEXT,
    applicable_rate_plans TEXT,
    usage_limit INT,
    usage_count INT DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_valid_dates (valid_from, valid_until),
    INDEX idx_is_active (is_active),
    INDEX idx_offer_type (offer_type)
);

-- CAMPAIGN PERFORMANCE TRACKING
CREATE TABLE IF NOT EXISTS campaign_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    performance_date DATE NOT NULL,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    leads INT DEFAULT 0,
    conversions INT DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0,
    spend DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_performance_date (performance_date),
    UNIQUE KEY unique_campaign_date (campaign_id, performance_date)
);

-- Sample Data for Marketing Campaigns
INSERT INTO marketing_campaigns (name, description, campaign_type, target_audience, start_date, end_date, budget, status, leads_generated, conversions, revenue_generated, roi_percentage, created_by) VALUES
('Summer Staycation Promo', 'Promote hotel stays for local families during summer break', 'promotion', 'Local families, young professionals', '2024-04-01', '2024-05-31', 50000.00, 'completed', 150, 45, 225000.00, 350.00, 1),
('Loyalty Program Launch', 'Introduce new loyalty program for repeat guests', 'loyalty', 'Existing guests, frequent travelers', '2024-02-01', '2024-12-31', 25000.00, 'active', 200, 80, 160000.00, 540.00, 1),
('Holiday Season Special', 'Special rates and packages for holiday season', 'seasonal', 'Families, couples, business travelers', '2024-11-01', '2024-12-31', 75000.00, 'active', 300, 120, 480000.00, 540.00, 1);

-- Sample Data for Promotional Offers
INSERT INTO promotional_offers (code, name, description, offer_type, discount_value, discount_percentage, min_stay_nights, max_discount_amount, applicable_room_types, usage_limit, usage_count, valid_from, valid_until, is_active) VALUES
('SUMMER20', 'Summer Discount 20%', '20% off on all room bookings during summer', 'percentage_discount', NULL, 20.00, 2, 5000.00, 'Single,Double,Deluxe,Suite', 100, 67, '2024-04-01', '2024-05-31', 0),
('LOYALTY15', 'Loyalty Member Discount', '15% off for loyalty program members', 'percentage_discount', NULL, 15.00, 1, 3000.00, 'Single,Double,Deluxe,Suite', NULL, 45, '2024-02-01', '2024-12-31', 1),
('HOLIDAY25', 'Holiday Special 25%', '25% off on deluxe and suite rooms during holidays', 'percentage_discount', NULL, 25.00, 3, 10000.00, 'Deluxe,Suite', 50, 23, '2024-11-01', '2024-12-31', 1);

-- Sample Data for Campaign Performance
INSERT INTO campaign_performance (campaign_id, performance_date, impressions, clicks, leads, conversions, revenue, spend) VALUES
(1, '2024-04-15', 5000, 250, 50, 15, 75000.00, 5000.00),
(1, '2024-04-30', 7500, 375, 75, 22, 110000.00, 7500.00),
(1, '2024-05-15', 6000, 300, 60, 18, 90000.00, 6000.00),
(2, '2024-03-15', 3000, 150, 30, 12, 48000.00, 2500.00),
(2, '2024-06-15', 4000, 200, 40, 16, 64000.00, 3000.00),
(2, '2024-09-15', 4500, 225, 45, 18, 72000.00, 3500.00),
(3, '2024-11-15', 8000, 400, 80, 32, 128000.00, 8000.00),
(3, '2024-12-01', 10000, 500, 100, 40, 160000.00, 10000.00),
(3, '2024-12-15', 12000, 600, 120, 48, 192000.00, 12000.00);
