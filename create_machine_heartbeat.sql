-- Create machine heartbeat table for tracking machine status
CREATE TABLE IF NOT EXISTS machine_heartbeat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id VARCHAR(50) NOT NULL DEFAULT 'rice_dispenser_1',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('online', 'offline', 'error') DEFAULT 'online',
    wifi_signal INT DEFAULT NULL,
    system_uptime INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_machine (machine_id)
);

-- Insert initial record for the rice dispenser
INSERT IGNORE INTO machine_heartbeat (machine_id, status) VALUES ('rice_dispenser_1', 'offline');
