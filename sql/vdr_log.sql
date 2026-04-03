CREATE TABLE IF NOT EXISTS vdr_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('debug', 'info', 'warn', 'error') NOT NULL DEFAULT 'info',
    source VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    context JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_source (source),
    INDEX idx_created (created_at)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;
