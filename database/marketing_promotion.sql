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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample marketing campaigns
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
('Luxury Experience Upgrade', 'Premium package upgrades and VIP services', 'loyalty', 'High-value customers, VIP members', '2023-04-01', '2023-12-31', 7000.00, 'active', 56, 15, 11200.00, 60.00, 1),
('Draft Campaign Example', 'Example campaign in draft status', 'email', 'Test audience', '2023-12-01', '2024-01-31', 1500.00, 'draft', 0, 0, 0.00, 0.00, 1),
('Paused Campaign', 'Temporarily paused campaign for budget reasons', 'advertising', 'General audience', '2023-05-01', '2023-08-31', 4500.00, 'paused', 23, 5, 1800.00, -60.00, 1);

-- Insert sample promotional offers
INSERT INTO promotional_offers (code, name, description, offer_type, discount_value, discount_percentage, min_stay_nights, max_discount_amount, applicable_room_types, applicable_rate_plans, usage_limit, valid_from, valid_until) VALUES
('SUMMER15', 'Summer 15% Discount', '15% discount on all room types during summer', 'percentage_discount', NULL, 15.00, 2, 150.00, 'All', 'Standard,Deluxe,Suite', 500, '2023-06-01', '2023-08-31'),
('WINTER50', 'Winter Fixed Discount', '$50 off per night during winter season', 'fixed_amount_discount', 50.00, NULL, 3, NULL, 'Deluxe,Suite', 'Deluxe,Suite', 200, '2023-11-01', '2024-01-15'),
('LOYALTY10', 'Loyalty Member Discount', '10% discount for loyalty program members', 'percentage_discount', NULL, 10.00, 1, 100.00, 'All', 'All', NULL, '2023-01-01', '2023-12-31'),
('STUDENT20', 'Student Special Rate', '20% discount for students with valid ID', 'percentage_discount', NULL, 20.00, 1, 200.00, 'Single,Double', 'Standard', 100, '2023-09-01', '2023-05-31'),
('BUSINESS15', 'Business Traveler Discount', '15% discount for business travelers', 'percentage_discount', NULL, 15.00, 2, 120.00, 'Deluxe,Suite', 'Business', 300, '2023-01-01', '2023-12-31'),
('ROMANCE100', 'Romantic Package', '$100 off romantic getaway packages', 'fixed_amount_discount', 100.00, NULL, 2, NULL, 'Suite', 'Romance', 50, '2023-02-01', '2023-02-28'),
('VIPUPGRADE', 'VIP Room Upgrade', 'Free upgrade to next room category', 'upgrade', NULL, NULL, 3, NULL, 'Single,Double,Deluxe', 'VIP', 25, '2023-04-01', '2023-12-31'),
('FAMILYDEAL', 'Family Package Deal', 'Kids stay free with parents', 'package_deal', NULL, NULL, 2, NULL, 'Double,Deluxe,Suite', 'Family', 75, '2023-06-01', '2023-08-31');

-- Insert sample campaign performance data
INSERT INTO campaign_performance (campaign_id, performance_date, impressions, clicks, leads, conversions, revenue, spend) VALUES
-- Summer Sale performance
(1, '2023-06-01', 15000, 1200, 45, 12, 8500.00, 5000.00),
(1, '2023-07-01', 18000, 1400, 38, 10, 7200.00, 4800.00),
(1, '2023-08-01', 12000, 900, 23, 6, 4200.00, 3200.00),

-- Winter Holiday performance
(2, '2023-11-01', 25000, 2000, 67, 18, 12000.00, 8000.00),
(2, '2023-12-01', 22000, 1800, 59, 16, 10800.00, 7200.00),

-- Loyalty Program performance
(3, '2023-09-01', 8000, 650, 89, 23, 6500.00, 3000.00),
(3, '2023-10-01', 7500, 600, 78, 20, 5800.00, 2800.00),
(3, '2023-11-01', 8200, 680, 85, 22, 6200.00, 3100.00),
(3, '2023-12-01', 7900, 640, 82, 21, 5900.00, 2950.00),

-- Black Friday performance
(4, '2023-11-15', 35000, 2800, 134, 31, 18500.00, 10000.00),
(4, '2023-11-20', 42000, 3200, 145, 35, 21000.00, 11500.00),
(4, '2023-11-25', 55000, 4200, 178, 42, 25200.00, 14000.00),
(4, '2023-11-30', 48000, 3600, 156, 38, 22800.00, 12500.00);</parameter,

SELECT 'Enhanced marketing and promotion system created successfully!' AS Status;