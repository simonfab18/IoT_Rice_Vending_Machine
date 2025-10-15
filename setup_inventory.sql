-- Create rice_inventory table
CREATE TABLE IF NOT EXISTS `rice_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('regular','premium','special') NOT NULL DEFAULT 'regular',
  `price` decimal(10,2) NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(10) NOT NULL DEFAULT 'kg',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default rice varieties
INSERT INTO `rice_inventory` (`name`, `type`, `price`, `stock`, `unit`, `created_at`) VALUES
('Regular Rice', 'regular', 60.00, 100.00, 'kg', NOW()),
('Premium Rice', 'premium', 80.00, 75.00, 'kg', NOW()); 