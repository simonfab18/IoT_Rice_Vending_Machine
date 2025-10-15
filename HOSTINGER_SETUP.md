# Hostinger Setup Guide for Rice Dispenser IoT

## 📋 Your Hostinger Details
- **Website**: orange-donkey-160020.hostingersite.com
- **IP Address**: 5.183.10.21
- **Database Name**: u895570689_rice_dispenser
- **Database User**: u895570689_farmart
- **Database Password**: Farmart123

## 🚀 Step-by-Step Setup

### Step 1: Upload Files to Hostinger
1. **Login to Hostinger** cPanel
2. **Go to File Manager**
3. **Navigate to public_html folder**
4. **Create folder**: `rice_dispenser_iot-master`
5. **Upload all PHP files** to this folder:
   - `database.php` (already updated)
   - `upload.php`
   - `get_rice_config.php`
   - `update_inventory.php`
   - `main.php`
   - `inventory.php`
   - `transaction.php`
   - `alerts.php`
   - `reports.php`
   - `simple_pdf_generator.php`
   - All other PHP files

### Step 2: Create Database Tables
1. **Go to phpMyAdmin** in Hostinger cPanel
2. **Select database**: `u895570689_rice_dispenser`
3. **Run these SQL commands** (in order):

```sql
-- 1. Main transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_amount INT NOT NULL,
    rice_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Rice inventory table
CREATE TABLE IF NOT EXISTS rice_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock DECIMAL(10,2) NOT NULL,
    capacity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'kg',
    expiration_date DATE,
    manufacturer VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Machine heartbeat table
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

-- 4. Alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('low_stock', 'expired', 'maintenance', 'storage') NOT NULL,
    message TEXT NOT NULL,
    status ENUM('active', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL
);

-- 5. Reports table
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    report_data LONGTEXT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Insert initial data
INSERT INTO machine_heartbeat (machine_id, status) VALUES ('rice_dispenser_1', 'offline');

-- 7. Insert sample rice data
INSERT INTO rice_inventory (name, price, stock, capacity, unit, expiration_date, manufacturer) VALUES
('Dinorado Rice', 60.00, 25.0, 25.0, 'kg', '2025-12-31', 'Farmart'),
('Jasmine Rice', 55.00, 20.0, 25.0, 'kg', '2025-12-31', 'Farmart');
```

### Step 3: Update Arduino Code
1. **Update WiFi credentials** in `rice.ino`:
   ```cpp
   const char* ssid = "YOUR_WIFI_NAME";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```

2. **Compile and upload** the updated Arduino code

### Step 4: Test Connection
1. **Visit your website**: https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/
2. **Check if main.php loads** correctly
3. **Test Arduino connection** by checking Serial Monitor

## 🔧 Troubleshooting

### If Arduino can't connect:
1. **Check WiFi credentials** in Arduino code
2. **Verify HTTPS connection** (some ESP32 boards need SSL certificate handling)
3. **Check firewall settings** in Hostinger

### If database errors occur:
1. **Verify database credentials** in `database.php`
2. **Check if all tables were created** in phpMyAdmin
3. **Ensure database user has proper permissions**

### If files don't load:
1. **Check file permissions** (should be 644 for PHP files)
2. **Verify all files are uploaded** to correct folder
3. **Check for PHP errors** in Hostinger error logs

## 📱 Access Your System
- **Main Dashboard**: https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/main.php
- **Inventory**: https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/inventory.php
- **Transactions**: https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/transaction.php
- **Reports**: https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/reports.php

## 🔒 Security Notes
- **Change default passwords** if any
- **Enable SSL** (should be automatic with Hostinger)
- **Regular backups** of database
- **Monitor access logs** for suspicious activity

## 📞 Support
If you encounter issues:
1. **Check Hostinger error logs**
2. **Verify Arduino Serial Monitor** for connection errors
3. **Test individual PHP files** by accessing them directly
4. **Check database connection** using a simple test script
