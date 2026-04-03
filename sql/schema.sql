-- Voudou Ropes Checklist - Database Schema
-- Run this against your MySQL database to create all tables

CREATE TABLE IF NOT EXISTS vdr_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    paid TINYINT(1) NOT NULL DEFAULT 0,
    payment_id VARCHAR(100) NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vdr_login_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES vdr_users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vdr_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_number INT NOT NULL UNIQUE,
    title VARCHAR(100) NOT NULL
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vdr_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    NAME VARCHAR(200) NOT NULL,
    video_url VARCHAR(500) NULL,
    video_thumbnail VARCHAR(500) NULL,
    description TEXT NULL,
    FOREIGN KEY (level_id) REFERENCES vdr_levels(id) ON DELETE CASCADE,
    INDEX idx_level_sort (level_id, sort_order)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vdr_user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    seen TINYINT(1) NOT NULL DEFAULT 0,
    done TINYINT(1) NOT NULL DEFAULT 0,
    seen_at DATETIME NULL,
    done_at DATETIME NULL,
    UNIQUE KEY uq_user_item (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES vdr_users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES vdr_items(id) ON DELETE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;
