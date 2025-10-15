-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 05:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rice_dispenser`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `type` enum('storage','maintenance','system') NOT NULL,
  `status` enum('active','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `message`, `type`, `status`, `created_at`, `resolved_at`) VALUES
(1, 'Low stock alert: Dinorado Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'resolved', '2025-08-24 09:22:09', '2025-08-24 09:22:21'),
(2, 'Low stock alert: Dinorado Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'resolved', '2025-08-24 09:22:23', '2025-08-24 09:22:31'),
(3, 'Low stock alert: Dinorado Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'resolved', '2025-08-24 09:22:36', '2025-08-24 09:23:28'),
(4, 'Low stock alert: Premium Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'resolved', '2025-08-24 09:24:03', '2025-08-25 18:19:43'),
(5, 'Low stock alert: Dinorado Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'resolved', '2025-08-24 09:26:25', '2025-08-25 18:19:39'),
(6, 'Low stock alert: Premium Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'active', '2025-08-25 18:19:44', NULL),
(7, 'Low stock alert: Joey Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'active', '2025-08-27 12:20:35', NULL),
(8, 'Low stock alert: Regular Rice is running low (Current: 1.00 kg / 10 kg)', 'storage', 'active', '2025-08-27 13:48:02', NULL),
(9, 'Low stock alert: Dinorado  is running low (Current: 0.99 kg / 10 kg)', 'storage', 'active', '2025-09-20 09:37:20', NULL),
(10, 'Low stock alert: Jasmine is running low (Current: 1.00 kg / 10 kg)', 'storage', 'active', '2025-09-20 15:50:17', NULL),
(11, 'Low stock alert: Coco 123 is running low (Current: 1.09 kg / 10 kg)', 'storage', 'active', '2025-09-20 15:50:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `export_configs`
--

CREATE TABLE `export_configs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('email','cloud','local') NOT NULL,
  `config` longtext NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `export_configs`
--

INSERT INTO `export_configs` (`id`, `name`, `type`, `config`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Email', 'email', '{\"smtp_host\": \"localhost\", \"smtp_port\": 587, \"smtp_secure\": \"tls\", \"from_email\": \"reports@farmart.com\", \"from_name\": \"Farmart Reports\"}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(2, 'Local Storage', 'local', '{\"path\": \"/reports/\", \"max_size\": \"100MB\", \"file_naming\": \"report_{type}_{date}_{time}\"}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `type` enum('sales','inventory','performance','financial') NOT NULL,
  `data` longtext NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `type`, `data`, `status`, `file_path`, `created_at`, `completed_at`) VALUES
(6, 'sales', '[{\"sale_date\":\"2025-06-10\",\"transactions\":\"3\",\"total_revenue\":\"180\",\"total_kilos\":\"4.00\",\"avg_transaction\":\"60.0000\"}]', 'completed', NULL, '2025-08-24 09:46:00', NULL),
(7, 'inventory', '[{\"name\":\"Dinorado Rice\",\"type\":\"regular\",\"stock\":\"1.00\",\"price\":\"57.00\",\"stock_value\":\"57.0000\"},{\"name\":\"Premium Rice\",\"type\":\"premium\",\"stock\":\"1.00\",\"price\":\"80.00\",\"stock_value\":\"80.0000\"}]', 'completed', NULL, '2025-08-24 09:46:24', NULL),
(8, 'sales', '[{\"sale_date\":\"2025-06-10\",\"transactions\":\"3\",\"total_revenue\":\"180\",\"total_kilos\":\"4.00\",\"avg_transaction\":\"60.0000\"}]', 'completed', NULL, '2025-08-24 09:46:51', NULL),
(9, 'sales', '[{\"sale_date\":\"2025-06-10\",\"transactions\":\"3\",\"total_revenue\":\"180\",\"total_kilos\":\"4.00\",\"avg_transaction\":\"60.0000\"}]', 'completed', NULL, '2025-08-24 09:47:02', NULL),
(10, 'sales', '[]', 'completed', NULL, '2025-08-24 09:47:17', NULL),
(11, 'sales', '[{\"sale_date\":\"2025-06-10\",\"transactions\":\"3\",\"total_revenue\":\"180\",\"total_kilos\":\"4.00\",\"avg_transaction\":\"60.0000\"}]', 'completed', NULL, '2025-08-24 09:50:42', NULL),
(12, 'inventory', '[{\"name\":\"Dinorado Rice\",\"type\":\"regular\",\"stock\":\"1.00\",\"price\":\"57.00\",\"stock_value\":\"57.0000\"},{\"name\":\"Premium Rice\",\"type\":\"premium\",\"stock\":\"1.00\",\"price\":\"80.00\",\"stock_value\":\"80.0000\"}]', 'completed', NULL, '2025-08-24 09:55:50', NULL),
(13, '', '[]', 'completed', NULL, '2025-08-24 09:55:50', NULL),
(14, 'inventory', '[{\"name\":\"Dinorado Rice\",\"type\":\"regular\",\"stock\":\"1.00\",\"price\":\"57.00\",\"stock_value\":\"57.0000\"},{\"name\":\"Premium Rice\",\"type\":\"premium\",\"stock\":\"1.00\",\"price\":\"80.00\",\"stock_value\":\"80.0000\"}]', 'completed', NULL, '2025-08-24 09:55:55', NULL),
(15, '', '[]', 'completed', NULL, '2025-08-24 09:55:55', NULL),
(16, '', '[]', 'completed', NULL, '2025-08-24 09:55:59', NULL),
(17, 'inventory', '[{\"name\":\"Dinorado Rice\",\"type\":\"regular\",\"stock\":\"1.00\",\"price\":\"57.00\",\"stock_value\":\"57.0000\"},{\"name\":\"Premium Rice\",\"type\":\"premium\",\"stock\":\"1.00\",\"price\":\"80.00\",\"stock_value\":\"80.0000\"}]', 'completed', NULL, '2025-08-24 09:56:09', NULL),
(18, '', '[]', 'completed', NULL, '2025-08-24 09:56:09', NULL),
(19, 'inventory', '[{\"name\":\"Dinorado Rice\",\"type\":\"regular\",\"stock\":\"1.00\",\"price\":\"57.00\",\"stock_value\":\"57.0000\"},{\"name\":\"Premium Rice\",\"type\":\"premium\",\"stock\":\"1.00\",\"price\":\"80.00\",\"stock_value\":\"80.0000\"}]', 'completed', NULL, '2025-08-24 10:08:51', NULL),
(20, 'sales', '[{\"sale_date\":\"2025-08-28\",\"transactions\":\"21\",\"total_revenue\":\"1290\",\"total_kilos\":\"21.00\",\"avg_transaction\":\"61.4286\"},{\"sale_date\":\"2025-08-27\",\"transactions\":\"53\",\"total_revenue\":\"4408\",\"total_kilos\":\"53.00\",\"avg_transaction\":\"83.1698\"},{\"sale_date\":\"2025-08-26\",\"transactions\":\"9\",\"total_revenue\":\"631\",\"total_kilos\":\"9.00\",\"avg_transaction\":\"70.1111\"}]', 'completed', NULL, '2025-08-28 05:51:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_schedules`
--

CREATE TABLE `report_schedules` (
  `id` int(11) NOT NULL,
  `report_type` enum('sales','inventory','performance','financial') NOT NULL,
  `frequency` enum('daily','weekly','monthly') NOT NULL,
  `time` time NOT NULL,
  `email` varchar(255) NOT NULL,
  `format` enum('pdf','excel','csv') NOT NULL DEFAULT 'pdf',
  `status` enum('active','paused','deleted') DEFAULT 'active',
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('sales','inventory','performance','financial') NOT NULL,
  `description` text DEFAULT NULL,
  `config` longtext NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_templates`
--

INSERT INTO `report_templates` (`id`, `name`, `type`, `description`, `config`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 'Daily Sales Summary', 'sales', 'Daily sales overview with revenue and transaction counts', '{\"period\": \"daily\", \"metrics\": [\"revenue\", \"transactions\", \"kilos\"], \"grouping\": \"date\"}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(2, 'Weekly Sales Analysis', 'sales', 'Weekly sales trends and patterns', '{\"period\": \"weekly\", \"metrics\": [\"revenue\", \"transactions\", \"kilos\"], \"grouping\": \"week\"}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(3, 'Monthly Sales Report', 'sales', 'Comprehensive monthly sales analysis', '{\"period\": \"monthly\", \"metrics\": [\"revenue\", \"transactions\", \"kilos\", \"avg_transaction\"], \"grouping\": \"month\"}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(4, 'Inventory Status', 'inventory', 'Current stock levels and reorder recommendations', '{\"metrics\": [\"stock\", \"price\", \"stock_value\"], \"thresholds\": {\"low_stock\": 2, \"critical_stock\": 1}}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(5, 'Performance Metrics', 'performance', 'System performance and efficiency metrics', '{\"metrics\": [\"uptime\", \"transactions_per_day\", \"revenue_per_transaction\"], \"period\": \"monthly\"}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(6, 'Financial Summary', 'financial', 'Revenue, costs, and profit analysis', '{\"metrics\": [\"revenue\", \"costs\", \"profit_margin\"], \"period\": \"monthly\", \"grouping\": \"month\"}', 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `retention_policies`
--

CREATE TABLE `retention_policies` (
  `id` int(11) NOT NULL,
  `data_type` enum('transactions','inventory','reports','alerts') NOT NULL,
  `retention_days` int(11) NOT NULL DEFAULT 365,
  `archive_after_days` int(11) DEFAULT NULL,
  `delete_after_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retention_policies`
--

INSERT INTO `retention_policies` (`id`, `data_type`, `retention_days`, `archive_after_days`, `delete_after_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'transactions', 1095, 365, 1095, 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(2, 'inventory', 1095, 365, 1095, 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(3, 'reports', 730, 90, 730, 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43'),
(4, 'alerts', 365, 30, 365, 1, '2025-08-24 09:37:43', '2025-08-24 09:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `rice_inventory`
--

CREATE TABLE `rice_inventory` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('regular','premium','special') NOT NULL DEFAULT 'regular',
  `price` decimal(10,2) NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(10) NOT NULL DEFAULT 'kg',
  `manufacturer` varchar(255) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rice_inventory`
--

INSERT INTO `rice_inventory` (`id`, `name`, `type`, `price`, `stock`, `unit`, `manufacturer`, `expiration_date`, `created_at`, `updated_at`) VALUES
(1, 'Jasmine', 'regular', 50.00, 1.00, 'kg', 'Unknown Manufacturer', '2026-09-20', '2025-08-24 07:56:37', '2025-09-20 15:44:59'),
(2, 'Coco 123', 'premium', 50.00, 1.09, 'kg', 'Unknown Manufacturer', '2026-09-20', '2025-08-24 07:56:37', '2025-09-20 15:44:59');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `rice_name` varchar(25) NOT NULL DEFAULT 'regular',
  `kilos` decimal(10,2) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `amount`, `rice_name`, `kilos`, `transaction_date`, `created_at`) VALUES
(11, 60, 'regular', 1.00, '2025-06-10 02:49:11', '2025-06-09 18:49:11'),
(12, 60, 'regular', 1.00, '2025-06-10 02:50:39', '2025-06-09 18:50:39'),
(13, 60, 'regular', 2.00, '2025-06-10 03:11:44', '2025-06-09 19:11:44'),
(14, 70, 'regular', 1.00, '2025-08-26 00:37:11', '2025-08-25 16:37:11'),
(15, 71, 'regular', 1.00, '2025-08-26 00:54:42', '2025-08-25 16:54:42'),
(16, 70, 'regular', 1.00, '2025-08-26 01:04:24', '2025-08-25 17:04:24'),
(17, 70, 'regular', 1.00, '2025-08-26 01:08:13', '2025-08-25 17:08:13'),
(18, 70, 'regular', 1.00, '2025-08-26 01:11:35', '2025-08-25 17:11:35'),
(19, 70, 'regular', 1.00, '2025-08-26 01:15:05', '2025-08-25 17:15:05'),
(20, 70, 'regular', 1.00, '2025-08-26 01:17:09', '2025-08-25 17:17:09'),
(21, 70, 'regular', 1.00, '2025-08-26 01:21:50', '2025-08-25 17:21:50'),
(22, 70, 'regular', 1.00, '2025-08-26 02:16:16', '2025-08-25 18:16:16'),
(23, 61, 'regular', 1.00, '2025-08-27 00:10:32', '2025-08-26 16:10:32'),
(24, 60, 'regular', 1.00, '2025-08-27 00:11:18', '2025-08-26 16:11:18'),
(25, 100, 'regular', 1.00, '2025-08-27 17:51:01', '2025-08-27 09:51:01'),
(26, 100, 'regular', 1.00, '2025-08-27 17:55:06', '2025-08-27 09:55:06'),
(27, 60, 'regular', 1.00, '2025-08-27 17:56:55', '2025-08-27 09:56:55'),
(28, 84, 'regular', 1.00, '2025-08-27 18:40:39', '2025-08-27 10:40:39'),
(29, 101, 'regular', 1.00, '2025-08-27 18:43:55', '2025-08-27 10:43:55'),
(30, 100, 'regular', 1.00, '2025-08-27 18:44:38', '2025-08-27 10:44:38'),
(31, 103, 'regular', 1.00, '2025-08-27 18:47:39', '2025-08-27 10:47:39'),
(32, 70, 'regular', 1.00, '2025-08-27 18:49:02', '2025-08-27 10:49:02'),
(33, 104, 'regular', 1.00, '2025-08-27 19:19:24', '2025-08-27 11:19:24'),
(34, 100, 'regular', 1.00, '2025-08-27 19:20:34', '2025-08-27 11:20:34'),
(35, 101, 'regular', 1.00, '2025-08-27 19:21:38', '2025-08-27 11:21:38'),
(36, 109, 'regular', 1.00, '2025-08-27 19:22:21', '2025-08-27 11:22:21'),
(37, 100, 'regular', 1.00, '2025-08-27 20:13:33', '2025-08-27 12:13:33'),
(38, 126, 'regular', 1.00, '2025-08-27 20:14:49', '2025-08-27 12:14:49'),
(39, 200, 'regular', 1.00, '2025-08-27 20:37:10', '2025-08-27 12:37:10'),
(40, 152, 'regular', 1.00, '2025-08-27 20:38:38', '2025-08-27 12:38:38'),
(41, 150, 'regular', 1.00, '2025-08-27 20:41:24', '2025-08-27 12:41:24'),
(42, 150, 'regular', 1.00, '2025-08-27 20:43:49', '2025-08-27 12:43:49'),
(43, 100, 'regular', 1.00, '2025-08-27 21:03:01', '2025-08-27 13:03:01'),
(44, 60, 'regular', 1.00, '2025-08-27 21:07:05', '2025-08-27 13:07:05'),
(45, 70, 'regular', 1.00, '2025-08-27 21:07:25', '2025-08-27 13:07:25'),
(46, 70, 'regular', 1.00, '2025-08-27 21:08:05', '2025-08-27 13:08:05'),
(47, 100, 'regular', 1.00, '2025-08-27 21:15:04', '2025-08-27 13:15:04'),
(48, 50, 'regular', 1.00, '2025-08-27 21:16:02', '2025-08-27 13:16:02'),
(49, 50, 'regular', 1.00, '2025-08-27 21:23:30', '2025-08-27 13:23:30'),
(50, 150, 'regular', 1.00, '2025-08-27 21:34:38', '2025-08-27 13:34:38'),
(51, 50, 'regular', 1.00, '2025-08-27 21:35:07', '2025-08-27 13:35:07'),
(52, 54, 'regular', 1.00, '2025-08-27 21:40:07', '2025-08-27 13:40:07'),
(53, 153, 'regular', 1.00, '2025-08-27 21:45:43', '2025-08-27 13:45:43'),
(54, 50, 'regular', 1.00, '2025-08-27 21:47:52', '2025-08-27 13:47:52'),
(55, 50, 'regular', 1.00, '2025-08-27 21:49:01', '2025-08-27 13:49:01'),
(56, 50, 'regular', 1.00, '2025-08-27 21:49:49', '2025-08-27 13:49:49'),
(57, 100, 'regular', 1.00, '2025-08-27 21:50:45', '2025-08-27 13:50:45'),
(58, 50, 'regular', 1.00, '2025-08-27 21:52:42', '2025-08-27 13:52:42'),
(59, 50, 'regular', 1.00, '2025-08-27 21:53:19', '2025-08-27 13:53:19'),
(60, 150, 'regular', 1.00, '2025-08-27 21:53:53', '2025-08-27 13:53:53'),
(61, 50, 'regular', 1.00, '2025-08-27 22:08:11', '2025-08-27 14:08:11'),
(62, 150, 'regular', 1.00, '2025-08-27 22:10:06', '2025-08-27 14:10:06'),
(63, 50, 'regular', 1.00, '2025-08-27 22:29:55', '2025-08-27 14:29:55'),
(64, 50, 'regular', 1.00, '2025-08-27 22:31:15', '2025-08-27 14:31:15'),
(65, 70, 'regular', 1.00, '2025-08-27 22:32:34', '2025-08-27 14:32:34'),
(66, 50, 'regular', 1.00, '2025-08-27 22:35:29', '2025-08-27 14:35:29'),
(67, 50, 'regular', 1.00, '2025-08-27 22:36:06', '2025-08-27 14:36:06'),
(68, 50, 'regular', 1.00, '2025-08-27 22:36:37', '2025-08-27 14:36:37'),
(69, 50, 'regular', 1.00, '2025-08-27 22:37:42', '2025-08-27 14:37:42'),
(70, 50, 'regular', 1.00, '2025-08-27 22:38:09', '2025-08-27 14:38:09'),
(71, 50, 'regular', 1.00, '2025-08-27 22:39:57', '2025-08-27 14:39:57'),
(72, 50, 'regular', 1.00, '2025-08-27 22:52:54', '2025-08-27 14:52:54'),
(73, 50, 'regular', 1.00, '2025-08-27 23:18:20', '2025-08-27 15:18:20'),
(74, 50, 'regular', 1.00, '2025-08-27 23:23:41', '2025-08-27 15:23:41'),
(75, 50, 'regular', 1.00, '2025-08-27 23:24:15', '2025-08-27 15:24:15'),
(76, 50, 'regular', 1.00, '2025-08-28 00:19:43', '2025-08-27 16:19:43'),
(77, 50, 'regular', 1.00, '2025-08-28 00:25:49', '2025-08-27 16:25:49'),
(78, 50, 'regular', 1.00, '2025-08-28 00:27:06', '2025-08-27 16:27:06'),
(79, 50, 'regular', 1.00, '2025-08-28 00:28:46', '2025-08-27 16:28:46'),
(80, 50, 'regular', 1.00, '2025-08-28 00:31:44', '2025-08-27 16:31:44'),
(81, 70, 'regular', 1.00, '2025-08-28 00:34:25', '2025-08-27 16:34:25'),
(82, 50, 'regular', 1.00, '2025-08-28 00:37:22', '2025-08-27 16:37:22'),
(83, 50, 'regular', 1.00, '2025-08-28 00:38:22', '2025-08-27 16:38:22'),
(84, 50, 'regular', 1.00, '2025-08-28 00:41:56', '2025-08-27 16:41:56'),
(85, 50, 'regular', 1.00, '2025-08-28 00:44:42', '2025-08-27 16:44:42'),
(86, 50, 'regular', 1.00, '2025-08-28 00:45:23', '2025-08-27 16:45:23'),
(87, 130, 'regular', 1.00, '2025-08-28 00:50:30', '2025-08-27 16:50:30'),
(88, 60, 'regular', 1.00, '2025-08-28 00:53:28', '2025-08-27 16:53:28'),
(89, 105, 'regular', 1.00, '2025-08-28 00:57:47', '2025-08-27 16:57:47'),
(90, 55, 'regular', 1.00, '2025-08-28 01:16:14', '2025-08-27 17:16:14'),
(91, 50, 'regular', 1.00, '2025-08-28 01:19:35', '2025-08-27 17:19:35'),
(92, 50, 'regular', 1.00, '2025-08-28 01:28:52', '2025-08-27 17:28:52'),
(93, 50, 'regular', 1.00, '2025-08-28 01:30:56', '2025-08-27 17:30:56'),
(94, 70, 'regular', 1.00, '2025-08-28 01:32:28', '2025-08-27 17:32:28'),
(95, 100, 'regular', 1.00, '2025-08-28 01:34:18', '2025-08-27 17:34:18'),
(96, 50, 'regular', 1.00, '2025-08-28 01:36:21', '2025-08-27 17:36:21'),
(97, 100, 'regular', 1.00, '2025-09-16 15:53:59', '2025-09-16 07:53:59'),
(98, 100, 'regular', 1.00, '2025-09-16 15:56:41', '2025-09-16 07:56:41'),
(99, 100, 'regular', 1.00, '2025-09-16 17:47:58', '2025-09-16 09:47:58'),
(100, 50, 'regular', 1.00, '2025-09-16 17:49:41', '2025-09-16 09:49:41'),
(101, 100, 'regular', 1.00, '2025-09-16 17:52:32', '2025-09-16 09:52:32'),
(102, 50, 'regular', 1.00, '2025-09-16 17:54:48', '2025-09-16 09:54:48'),
(103, 65, 'regular', 1.00, '2025-09-16 19:18:23', '2025-09-16 11:18:23'),
(104, 200, 'regular', 1.00, '2025-09-16 19:21:31', '2025-09-16 11:21:31'),
(105, 55, 'regular', 1.00, '2025-09-16 19:25:41', '2025-09-16 11:25:41'),
(106, 55, 'regular', 1.00, '2025-09-16 19:43:50', '2025-09-16 11:43:50'),
(107, 55, 'regular', 1.00, '2025-09-16 19:46:34', '2025-09-16 11:46:34'),
(108, 50, 'regular', 1.00, '2025-09-16 20:10:24', '2025-09-16 12:10:24'),
(109, 70, 'regular', 1.00, '2025-09-16 20:13:02', '2025-09-16 12:13:02'),
(110, 50, 'regular', 1.00, '2025-09-18 21:24:09', '2025-09-18 13:24:09'),
(111, 50, 'regular', 1.00, '2025-09-18 21:27:35', '2025-09-18 13:27:35'),
(112, 50, 'regular', 1.00, '2025-09-18 21:57:34', '2025-09-18 13:57:34'),
(113, 50, 'regular', 1.00, '2025-09-18 22:10:56', '2025-09-18 14:10:56'),
(114, 50, 'regular', 1.00, '2025-09-18 22:40:49', '2025-09-18 14:40:49'),
(115, 50, 'regular', 1.00, '2025-09-18 22:43:09', '2025-09-18 14:43:09'),
(116, 60, 'regular', 1.00, '2025-09-18 22:55:59', '2025-09-18 14:55:59'),
(117, 50, 'regular', 1.00, '2025-09-18 22:56:56', '2025-09-18 14:56:56'),
(118, 50, 'regular', 1.00, '2025-09-18 23:46:55', '2025-09-18 15:46:55'),
(119, 50, 'regular', 1.00, '2025-09-18 23:48:49', '2025-09-18 15:48:49'),
(120, 91, 'regular', 1.00, '2025-09-19 16:42:54', '2025-09-19 08:42:54'),
(121, 50, 'metsadada rice', 1.00, '2025-09-20 12:04:02', '2025-09-20 04:04:02'),
(122, 50, 'metsadada rice', 1.00, '2025-09-20 12:40:53', '2025-09-20 04:40:53'),
(123, 50, 'metsadada rice', 1.00, '2025-09-20 12:41:35', '2025-09-20 04:41:35'),
(124, 50, 'Dinorado ', 1.00, '2025-09-20 12:56:44', '2025-09-20 04:56:44'),
(125, 50, 'Dinorado ', 1.00, '2025-09-20 12:58:26', '2025-09-20 04:58:26'),
(126, 50, 'Dinorado ', 1.00, '2025-09-20 13:00:10', '2025-09-20 05:00:10'),
(127, 50, 'Dinorado ', 1.00, '2025-09-20 13:00:56', '2025-09-20 05:00:56'),
(128, 50, 'Dinorado ', 1.00, '2025-09-20 18:00:49', '2025-09-20 10:00:49'),
(129, 50, 'Dinorado ', 1.00, '2025-09-20 18:01:31', '2025-09-20 10:01:31'),
(130, 50, 'Dinorado ', 1.00, '2025-09-20 18:03:06', '2025-09-20 10:03:06'),
(131, 50, 'Dinorado ', 1.00, '2025-09-20 18:03:45', '2025-09-20 10:03:45'),
(132, 50, 'Coco', 1.00, '2025-09-20 18:23:55', '2025-09-20 10:23:55'),
(133, 50, 'Coco', 1.00, '2025-09-20 18:24:47', '2025-09-20 10:24:47'),
(134, 70, 'Jasmine', 1.00, '2025-09-20 19:56:21', '2025-09-20 11:56:21'),
(135, 70, 'Jasmine', 1.00, '2025-09-20 19:57:09', '2025-09-20 11:57:09'),
(136, 70, 'Jasmine', 1.00, '2025-09-20 22:57:09', '2025-09-20 14:57:09'),
(137, 70, 'Jasmine', 1.00, '2025-09-20 22:57:48', '2025-09-20 14:57:48'),
(138, 50, 'Coco', 1.00, '2025-09-20 22:58:18', '2025-09-20 14:58:18'),
(139, 50, 'Coco', 1.00, '2025-09-20 22:59:07', '2025-09-20 14:59:07'),
(140, 50, 'Jasmine', 1.00, '2025-09-20 23:11:17', '2025-09-20 15:11:17'),
(141, 50, 'Jasmine', 1.00, '2025-09-20 23:12:16', '2025-09-20 15:12:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `export_configs`
--
ALTER TABLE `export_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_reports_type_status` (`type`,`status`);

--
-- Indexes for table `report_schedules`
--
ALTER TABLE `report_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_next_run` (`next_run`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_schedules_status_next_run` (`status`,`next_run`);

--
-- Indexes for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_default` (`is_default`),
  ADD KEY `idx_templates_type_default` (`type`,`is_default`);

--
-- Indexes for table `retention_policies`
--
ALTER TABLE `retention_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_data_type` (`data_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `rice_inventory`
--
ALTER TABLE `rice_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `export_configs`
--
ALTER TABLE `export_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `report_schedules`
--
ALTER TABLE `report_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_templates`
--
ALTER TABLE `report_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `retention_policies`
--
ALTER TABLE `retention_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rice_inventory`
--
ALTER TABLE `rice_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
