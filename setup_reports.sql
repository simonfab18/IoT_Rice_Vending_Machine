-- Reports System Database Setup
-- This file creates the necessary tables for the reports management system

-- Table for storing generated reports
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('sales','inventory','performance','financial') NOT NULL,
  `data` longtext NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing report schedules
CREATE TABLE IF NOT EXISTS `report_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` enum('sales','inventory','performance','financial') NOT NULL,
  `frequency` enum('daily','weekly','monthly') NOT NULL,
  `time` time NOT NULL,
  `email` varchar(255) NOT NULL,
  `format` enum('pdf','excel','csv') NOT NULL DEFAULT 'pdf',
  `status` enum('active','paused','deleted') DEFAULT 'active',
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_next_run` (`next_run`),
  KEY `idx_report_type` (`report_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing report templates
CREATE TABLE IF NOT EXISTS `report_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('sales','inventory','performance','financial') NOT NULL,
  `description` text,
  `config` longtext NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing export configurations
CREATE TABLE IF NOT EXISTS `export_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('email','cloud','local') NOT NULL,
  `config` longtext NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing data retention policies
CREATE TABLE IF NOT EXISTS `retention_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_type` enum('transactions','inventory','reports','alerts') NOT NULL,
  `retention_days` int(11) NOT NULL DEFAULT 365,
  `archive_after_days` int(11) DEFAULT NULL,
  `delete_after_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_data_type` (`data_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default report templates
INSERT INTO `report_templates` (`name`, `type`, `description`, `config`, `is_default`) VALUES
('Daily Sales Summary', 'sales', 'Daily sales overview with revenue and transaction counts', '{"period": "daily", "metrics": ["revenue", "transactions", "kilos"], "grouping": "date"}', 1),
('Weekly Sales Analysis', 'sales', 'Weekly sales trends and patterns', '{"period": "weekly", "metrics": ["revenue", "transactions", "kilos"], "grouping": "week"}', 1),
('Monthly Sales Report', 'sales', 'Comprehensive monthly sales analysis', '{"period": "monthly", "metrics": ["revenue", "transactions", "kilos", "avg_transaction"], "grouping": "month"}', 1),
('Inventory Status', 'inventory', 'Current stock levels and reorder recommendations', '{"metrics": ["stock", "price", "stock_value"], "thresholds": {"low_stock": 2, "critical_stock": 1}}', 1),
('Performance Metrics', 'performance', 'System performance and efficiency metrics', '{"metrics": ["uptime", "transactions_per_day", "revenue_per_transaction"], "period": "monthly"}', 1),
('Financial Summary', 'financial', 'Revenue, costs, and profit analysis', '{"metrics": ["revenue", "costs", "profit_margin"], "period": "monthly", "grouping": "month"}', 1);

-- Insert default retention policies
INSERT INTO `retention_policies` (`data_type`, `retention_days`, `archive_after_days`, `delete_after_days`) VALUES
('transactions', 1095, 365, 1095),  -- Keep 3 years, archive after 1 year
('inventory', 1095, 365, 1095),     -- Keep 3 years, archive after 1 year
('reports', 730, 90, 730),          -- Keep 2 years, archive after 3 months
('alerts', 365, 30, 365);           -- Keep 1 year, archive after 1 month

-- Insert default export configurations
INSERT INTO `export_configs` (`name`, `type`, `config`, `is_active`) VALUES
('Default Email', 'email', '{"smtp_host": "localhost", "smtp_port": 587, "smtp_secure": "tls", "from_email": "reports@farmart.com", "from_name": "Farmart Reports"}', 1),
('Local Storage', 'local', '{"path": "/reports/", "max_size": "100MB", "file_naming": "report_{type}_{date}_{time}"}', 1);

-- Create indexes for better performance
CREATE INDEX `idx_reports_type_status` ON `reports` (`type`, `status`);
CREATE INDEX `idx_schedules_status_next_run` ON `report_schedules` (`status`, `next_run`);
CREATE INDEX `idx_templates_type_default` ON `report_templates` (`type`, `is_default`); 