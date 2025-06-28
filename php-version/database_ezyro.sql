-- =====================================================
-- DATABASE SETUP UNTUK EZYRO HOSTING
-- =====================================================
-- Database: ezyro_39270123_ahay
-- Jalankan SQL ini di phpMyAdmin panel hosting Ezyro Anda

-- Table untuk users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table untuk tiktok boosts
CREATE TABLE IF NOT EXISTS tiktok_boosts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_url VARCHAR(500) NOT NULL,
    service_id INT NOT NULL DEFAULT 838,
    order_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    views_added INT DEFAULT 0,
    processing_time VARCHAR(50),
    video_title VARCHAR(500),
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index untuk performa dan pencarian cepat
CREATE INDEX idx_tiktok_boosts_ip_date ON tiktok_boosts(ip_address, DATE(created_at));
CREATE INDEX idx_tiktok_boosts_date ON tiktok_boosts(DATE(created_at));
CREATE INDEX idx_tiktok_boosts_status ON tiktok_boosts(status);
CREATE INDEX idx_tiktok_boosts_service ON tiktok_boosts(service_id);

-- Insert data contoh untuk testing (opsional)
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$example.hash.here') ON DUPLICATE KEY UPDATE username=username;

-- Tampilkan struktur table yang sudah dibuat
SELECT 'Tables created successfully!' as Status;
SHOW TABLES;
DESCRIBE tiktok_boosts;

-- Query untuk cek data hari ini (untuk testing)
-- SELECT COUNT(*) as total_today FROM tiktok_boosts WHERE DATE(created_at) = CURDATE();