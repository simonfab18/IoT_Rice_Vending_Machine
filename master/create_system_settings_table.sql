-- System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','number','boolean','json','email','url') DEFAULT 'string',
  `category` varchar(50) NOT NULL,
  `description` text,
  `is_editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`) VALUES
-- Machine Settings
('machine_name', 'Rice Dispenser #1', 'string', 'machine', 'Name of the rice dispensing machine'),
('machine_location', 'Main Store', 'string', 'machine', 'Physical location of the machine'),
('machine_timezone', 'Asia/Manila', 'string', 'machine', 'Timezone for the machine operations'),
('dispense_timeout', '30', 'number', 'machine', 'Timeout in seconds for dispensing operation'),
('maintenance_interval', '30', 'number', 'machine', 'Maintenance interval in days'),

-- Notification Settings
('email_notifications', 'true', 'boolean', 'notifications', 'Enable email notifications'),
('sms_notifications', 'false', 'boolean', 'notifications', 'Enable SMS notifications'),
('notification_email', 'admin@farmart.com', 'email', 'notifications', 'Email address for notifications'),
('low_stock_threshold', '2', 'number', 'notifications', 'Low stock threshold in kg'),
('alert_check_interval', '60', 'number', 'notifications', 'Alert check interval in minutes'),

-- System Settings
('system_name', 'Farmart Rice Dispenser', 'string', 'system', 'Name of the system'),
('system_version', '1.0.0', 'string', 'system', 'Current system version'),
('backup_enabled', 'true', 'boolean', 'system', 'Enable automatic backups'),
('backup_frequency', 'daily', 'string', 'system', 'Backup frequency (daily, weekly, monthly)'),
('data_retention_days', '365', 'number', 'system', 'Data retention period in days'),

-- WiFi Settings
('wifi_ssid', '', 'string', 'network', 'WiFi network name'),
('wifi_password', '', 'string', 'network', 'WiFi password'),
('wifi_timeout', '30', 'number', 'network', 'WiFi connection timeout in seconds'),
('auto_reconnect', 'true', 'boolean', 'network', 'Auto-reconnect to WiFi'),

-- Display Settings
('display_brightness', '80', 'number', 'display', 'Display brightness percentage'),
('display_timeout', '300', 'number', 'display', 'Display timeout in seconds'),
('show_price', 'true', 'boolean', 'display', 'Show prices on display'),
('show_stock', 'true', 'boolean', 'display', 'Show stock levels on display'),

-- Security Settings
('session_timeout', '3600', 'number', 'security', 'Session timeout in seconds'),
('max_login_attempts', '5', 'number', 'security', 'Maximum login attempts'),
('password_min_length', '6', 'number', 'security', 'Minimum password length'),
('enable_2fa', 'false', 'boolean', 'security', 'Enable two-factor authentication'),

-- Report Settings
('auto_reports', 'true', 'boolean', 'reports', 'Enable automatic report generation'),
('report_frequency', 'daily', 'string', 'reports', 'Report generation frequency'),
('report_email', 'reports@farmart.com', 'email', 'reports', 'Email for reports'),
('report_format', 'pdf', 'string', 'reports', 'Default report format'),

-- Currency Settings
('currency_symbol', '₱', 'string', 'currency', 'Currency symbol'),
('currency_code', 'PHP', 'string', 'currency', 'Currency code'),
('decimal_places', '2', 'number', 'currency', 'Number of decimal places'),

-- Maintenance Settings
('maintenance_mode', 'false', 'boolean', 'maintenance', 'Enable maintenance mode'),
('maintenance_message', 'System is under maintenance. Please try again later.', 'string', 'maintenance', 'Maintenance mode message'),
('auto_maintenance', 'false', 'boolean', 'maintenance', 'Enable automatic maintenance'),
('maintenance_schedule', '{"day": "sunday", "time": "02:00"}', 'json', 'maintenance', 'Maintenance schedule');
