-- Create table for Arduino serial logs
CREATE TABLE IF NOT EXISTS arduino_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id VARCHAR(50) NOT NULL DEFAULT 'rice_dispenser_1',
    log_level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'SYSTEM') NOT NULL DEFAULT 'INFO',
    log_message TEXT NOT NULL,
    log_category VARCHAR(50) DEFAULT NULL, -- e.g., 'WiFi', 'Database', 'Button', 'Inventory', etc.
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_machine_id (machine_id),
    INDEX idx_log_level (log_level),
    INDEX idx_timestamp (timestamp),
    INDEX idx_category (log_category)
);

-- Add some sample data for testing
INSERT INTO arduino_logs (machine_id, log_level, log_message, log_category) VALUES
('rice_dispenser_1', 'SYSTEM', 'Arduino system started successfully', 'System'),
('rice_dispenser_1', 'INFO', 'WiFi connected to network', 'WiFi'),
('rice_dispenser_1', 'INFO', 'Rice configuration loaded from server', 'Config'),
('rice_dispenser_1', 'DEBUG', 'Button A pressed - rice selection triggered', 'Button'),
('rice_dispenser_1', 'INFO', 'Transaction completed successfully', 'Transaction'),
('rice_dispenser_1', 'WARNING', 'Low stock detected for Rice A', 'Inventory'),
('rice_dispenser_1', 'ERROR', 'Failed to connect to WiFi', 'WiFi');
