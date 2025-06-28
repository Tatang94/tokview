-- Create database
CREATE DATABASE IF NOT EXISTS tiktok_booster;
USE tiktok_booster;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create tiktok_boosts table
CREATE TABLE IF NOT EXISTS tiktok_boosts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_url VARCHAR(500) NOT NULL,
    service_id INT NOT NULL,
    order_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    views_added INT DEFAULT 0,
    processing_time VARCHAR(50),
    video_title VARCHAR(500),
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_tiktok_boosts_ip_date ON tiktok_boosts(ip_address, DATE(created_at));
CREATE INDEX idx_tiktok_boosts_date ON tiktok_boosts(DATE(created_at));
CREATE INDEX idx_tiktok_boosts_status ON tiktok_boosts(status);