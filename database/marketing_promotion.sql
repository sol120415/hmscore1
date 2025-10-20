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
