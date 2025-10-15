# Rice Dispenser IoT System

An automated rice dispensing system with IoT capabilities, built using ESP32 and PHP/MySQL.

## Features

- Automated rice dispensing
- Real-time transaction monitoring
- Web-based dashboard
- Transaction history and analytics
- Low storage alerts
- CSV export functionality

## Hardware Requirements

- ESP32 Development Board
- Servo Motor
- LCD Display (I2C)
- Coin Acceptor
- Rice Storage Container
- Power Supply

## Software Requirements

- XAMPP (PHP 7.4+ and MySQL 5.7+)
- Arduino IDE
- ESP32 Board Support Package

## Installation

1. Clone the repository:
```bash
git clone https://github.com/Anoncasphil/rice_dispenser_iot.git
```

2. Set up the database:
   - Import `database.sql` to your MySQL server
   - Configure database connection in `database.php`

3. Configure ESP32:
   - Open `rice.ino` in Arduino IDE
   - Install required libraries
   - Update WiFi credentials
   - Upload to ESP32

4. Web Interface:
   - Place all PHP files in your XAMPP htdocs directory
   - Access the dashboard at `http://localhost/rice/main.php`

## File Structure

- `rice.ino` - ESP32 Arduino code
- `database.sql` - Database schema
- `database.php` - Database configuration
- `upload.php` - API endpoint for ESP32
- `main.php` - Dashboard
- `transaction.php` - Transaction history
- `alerts.php` - System alerts
- `style.css` - Styling

## Usage

1. Power on the ESP32
2. Insert coins (60 pesos for 1 kilo)
3. System will dispense rice automatically
4. Monitor transactions through the web dashboard
5. Receive alerts for low storage

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 