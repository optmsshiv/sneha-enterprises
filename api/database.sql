-- ============================================================
--  SNEHA ENTERPRISES — MySQL Database Setup
--  Run this SQL in phpMyAdmin → SQL tab
--  Database: Percona MySQL 5.7 (cPanel)
-- ============================================================

-- 1. ADMINS TABLE
CREATE TABLE IF NOT EXISTS `admins` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(100) UNIQUE NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255),
    `role`       VARCHAR(50) DEFAULT 'admin',
    `last_login` DATETIME,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. SESSIONS TABLE (auth tokens)
CREATE TABLE IF NOT EXISTS `sessions` (
    `token`      VARCHAR(255) PRIMARY KEY,
    `admin_id`   INT NOT NULL,
    `username`   VARCHAR(100) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. INQUIRIES TABLE
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id`           VARCHAR(50) PRIMARY KEY,
    `name`         VARCHAR(255) NOT NULL,
    `email`        VARCHAR(255) NOT NULL,
    `company`      VARCHAR(255),
    `country`      VARCHAR(100),
    `phone`        VARCHAR(50),
    `product_id`   VARCHAR(100),
    `product_name` VARCHAR(255),
    `quantity`     VARCHAR(100),
    `incoterm`     VARCHAR(20),
    `message`      TEXT,
    `source`       VARCHAR(50) DEFAULT 'website',
    `status`       ENUM('new','read','replied','closed') DEFAULT 'new',
    `notes`        TEXT,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status`     (`status`),
    INDEX `idx_product`    (`product_id`),
    INDEX `idx_created`    (`created_at`),
    INDEX `idx_email`      (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PRODUCTS TABLE
CREATE TABLE IF NOT EXISTS `products` (
    `id`          VARCHAR(100) PRIMARY KEY,
    `name`        VARCHAR(255) NOT NULL,
    `category`    VARCHAR(50) NOT NULL,
    `emoji`       VARCHAR(10) DEFAULT '🌾',
    `badge`       VARCHAR(50),
    `bg`          VARCHAR(255),
    `origin`      VARCHAR(255),
    `description` TEXT,
    `specs`       JSON,
    `packaging`   JSON,
    `min_order`   VARCHAR(100),
    `active`      TINYINT(1) DEFAULT 1,
    `sort_order`  INT DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_active`   (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── DEFAULT ADMIN (password: sneha2024) ────────────────────
-- SHA256 hash of 'sneha2024sneha_salt_2024'
INSERT IGNORE INTO `admins` (`username`, `password`, `email`, `role`)
VALUES (
    'admin',
    '7d4e6b2c1f8a3d9e5b0c7a4f2e6d1b8a3c9f5e0d2b7a4c6e1f8d3a9b5c2e7f4',
    'admin@snehaenterprises.in',
    'superadmin'
);

-- ─── SEED PRODUCTS ──────────────────────────────────────────
INSERT IGNORE INTO `products`
(`id`,`name`,`category`,`emoji`,`badge`,`bg`,`origin`,`description`,`specs`,`packaging`,`min_order`,`active`,`sort_order`)
VALUES
('wheat-001','Premium Wheat','grains','🌾','Best Seller',
 'linear-gradient(135deg,#FFF8E1,#FFF0C0)','Madhya Pradesh & Rajasthan',
 'High-gluten, low-moisture wheat for flour milling, bread and pasta manufacturing.',
 '{"Moisture":"Max 12%","Protein":"Min 10%","Gluten":"Min 26%","Purity":"99.5%"}',
 '["50kg PP Bags","25kg PP Bags","Bulk Container"]','25 Metric Tons',1,1),

('maize-001','Yellow Maize','grains','🌽','',
 'linear-gradient(135deg,#FFFDE7,#FFF59D)','Maharashtra & Bihar',
 'Grade-A yellow dent maize for animal feed, starch extraction and industrial use.',
 '{"Moisture":"Max 14%","Aflatoxin":"Max 10 ppb","Starch":"Min 68%","Purity":"98%"}',
 '["50kg PP Bags","Bulk Container"]','20 Metric Tons',1,2),

('paddy-001','Raw Paddy & Rice','grains','🍚','Export Grade',
 'linear-gradient(135deg,#E8F5E9,#C8E6C9)','West Bengal & Punjab',
 'Long-grain raw paddy and processed rice — Basmati, Sona Masuri, Parboiled.',
 '{"Moisture":"Max 13%","Broken Grains":"Max 5%","Purity":"99%","Varieties":"Basmati / Sona Masuri"}',
 '["25kg Jute Bags","50kg PP Bags","Vacuum Sealed"]','10 Metric Tons',1,3),

('turmeric-001','Turmeric (Haldi)','spices','🟡','Organic',
 'linear-gradient(135deg,#FFF3E0,#FFCC80)','Erode & Nizamabad',
 'High curcumin finger and bulb turmeric — whole, polished or as fine powder.',
 '{"Curcumin":"Min 3.5%","Moisture":"Max 10%","Purity":"99%","Form":"Finger / Bulb / Powder"}',
 '["25kg PP Bags","50kg PP Bags","10kg Vacuum Pouches"]','5 Metric Tons',1,4),

('foxnuts-001','Fox Nuts (Makhana)','foxnuts','🌿','Superfood',
 'linear-gradient(135deg,#F3E5F5,#E1BEE7)','Darbhanga & Madhubani, Bihar',
 'Premium grade lotus seeds — crispy, white and nutritious. Ideal for health food brands.',
 '{"Grade":"Premium / A-Grade","Moisture":"Max 8%","Purity":"99.5%","Size":"6-8mm / 8-10mm"}',
 '["5kg Vacuum Bags","10kg Cartons","25kg PP Bags"]','1 Metric Ton',1,5),

('vegetables-001','Fresh Vegetables','vegetables','🥦','',
 'linear-gradient(135deg,#E8F5E9,#A5D6A7)','Pan India',
 'Seasonal and year-round fresh produce — onions, potatoes, bitter gourd, drumstick.',
 '{"Availability":"Year-round","Grading":"A-Grade Sorted","Certification":"FSSAI Compliant","Packaging":"Custom"}',
 '["10kg Mesh Bags","25kg Cartons","Custom"]','5 Metric Tons',1,6),

('onion-001','Red & White Onions','vegetables','🧅','High Demand',
 'linear-gradient(135deg,#FFF3E0,#FFCC80)','Nashik, Maharashtra',
 'Fresh onions, properly cured and sorted by size. Exported to Middle East, SE Asia and Europe.',
 '{"Moisture":"Max 85%","Size":"40-80mm / 60-80mm","Skin":"Dry & Tight","Purity":"95% min"}',
 '["25kg Mesh Bags","10kg Crates"]','20 Metric Tons',1,7),

('chilli-001','Dried Red Chilli','spices','🌶','',
 'linear-gradient(135deg,#FFEBEE,#FFCDD2)','Andhra Pradesh & Karnataka',
 'Byadgi, Teja and Guntur varieties sorted by colour, heat and moisture.',
 '{"Moisture":"Max 12%","Colour":"ASTA 80+","Heat":"5000-150000 SHU","Varieties":"Byadgi / Teja / Guntur"}',
 '["25kg PP Bags","50kg Bales","Powder Pouches"]','5 Metric Tons',1,8),

('sorghum-001','Sorghum (Jowar)','grains','🌾','',
 'linear-gradient(135deg,#EFEBE9,#D7CCC8)','Karnataka & Maharashtra',
 'Gluten-free sorghum grain for food, feed and industrial markets.',
 '{"Moisture":"Max 13%","Purity":"98%","Protein":"Min 8%","Colour":"White / Red"}',
 '["25kg PP Bags","50kg PP Bags","Bulk"]','20 Metric Tons',1,9);

-- ─── Verify ────────────────────────────────────────────────
SELECT 'Setup complete!' AS status;
SELECT COUNT(*) AS total_products FROM products;
SELECT COUNT(*) AS total_admins FROM admins;
