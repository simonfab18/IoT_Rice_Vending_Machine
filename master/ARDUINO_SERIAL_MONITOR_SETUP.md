# Arduino Serial Monitor Setup Guide

This guide will help you set up a real-time serial monitor for your Arduino rice dispenser that displays logs in the website interface.

## 🚀 Quick Setup

### Step 1: Database Setup
1. Run the SQL script to create the logs table:
   ```sql
   -- Run this in your database
   SOURCE create_arduino_logs_table.sql;
   ```

### Step 2: Test the System
1. Visit `test_logging_system.php` in your browser to verify everything is working
2. Check that the database table was created and test logs were inserted

### Step 3: Access the Serial Monitor
1. Log into your admin panel
2. Click on "Serial Monitor" in the sidebar
3. You should see a terminal-like interface with logs

## 📁 Files Created

- `create_arduino_logs_table.sql` - Database table creation script
- `arduino_logs_api.php` - API endpoint for receiving logs from Arduino
- `logs.php` - Web interface for viewing logs (like Arduino Serial Monitor)
- `test_logging_system.php` - Test script to verify the system works
- `arduino_logging_additions.txt` - Code additions for Arduino
- `arduino_code_modifications.txt` - Detailed modification guide

## 🔧 Arduino Code Modifications

### Required Changes to `rice/rice.ino`:

1. **Add URL constant** (around line 45):
   ```cpp
   const char* logsUrl = "https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/arduino_logs_api.php";
   ```

2. **Add logging macros** (near the top with other defines):
   ```cpp
   #define LOG_DEBUG(msg, cat) do { Serial.println("[DEBUG] " + String(msg)); sendLogToServer("DEBUG", String(msg), String(cat)); } while(0)
   #define LOG_INFO(msg, cat) do { Serial.println("[INFO] " + String(msg)); sendLogToServer("INFO", String(msg), String(cat)); } while(0)
   #define LOG_WARNING(msg, cat) do { Serial.println("[WARNING] " + String(msg)); sendLogToServer("WARNING", String(msg), String(cat)); } while(0)
   #define LOG_ERROR(msg, cat) do { Serial.println("[ERROR] " + String(msg)); sendLogToServer("ERROR", String(msg), String(cat)); } while(0)
   #define LOG_SYSTEM(msg, cat) do { Serial.println("[SYSTEM] " + String(msg)); sendLogToServer("SYSTEM", String(msg), String(cat)); } while(0)
   ```

3. **Add logging function** (after `sendInventoryToServer` function):
   ```cpp
   void sendLogToServer(String level, String message, String category = "") {
     if (WiFi.status() == WL_CONNECTED) {
       HTTPClient http;
       http.begin(logsUrl);
       http.addHeader("Content-Type", "application/json");
       
       String jsonPayload = "{";
       jsonPayload += "\"machine_id\":\"rice_dispenser_1\",";
       jsonPayload += "\"log_level\":\"" + level + "\",";
       jsonPayload += "\"log_message\":\"" + message + "\"";
       if (category.length() > 0) {
         jsonPayload += ",\"log_category\":\"" + category + "\"";
       }
       jsonPayload += "}";
       
       int httpResponseCode = http.POST(jsonPayload);
       http.end();
     }
   }
   ```

4. **Replace Serial.println calls** with logging macros:
   ```cpp
   // Instead of:
   Serial.println("[WiFi] Connected successfully!");
   
   // Use:
   LOG_INFO("Connected successfully!", "WiFi");
   ```

## 🎯 Features

### Web Interface Features:
- **Real-time Updates**: Auto-refresh every 10 seconds (configurable)
- **Filtering**: Filter by log level (DEBUG, INFO, WARNING, ERROR, SYSTEM) and category
- **Auto-scroll**: Automatically scroll to latest logs
- **Statistics**: View total logs, errors, and warnings
- **Clear Logs**: Clear all logs with confirmation
- **Terminal-like UI**: Dark theme with colored log levels

### Log Levels:
- **DEBUG**: Detailed debugging information
- **INFO**: General information messages
- **WARNING**: Warning messages that don't stop operation
- **ERROR**: Error messages that indicate problems
- **SYSTEM**: System startup and critical events

### Log Categories:
- **WiFi**: Network connection events
- **Database**: Database operations
- **Button**: Button press events
- **Inventory**: Stock level updates
- **Transaction**: Payment and dispensing events
- **System**: General system events
- **Config**: Configuration loading
- **Printer**: Receipt printing events

## 🔍 Usage Examples

### In Arduino Code:
```cpp
// System startup
LOG_SYSTEM("Arduino system started successfully", "System");

// WiFi events
LOG_INFO("Connected to WiFi network", "WiFi");
LOG_ERROR("Failed to connect to WiFi", "WiFi");

// Button events
LOG_DEBUG("Button A pressed - rice selection triggered", "Button");

// Transaction events
LOG_INFO("Transaction completed successfully", "Transaction");

// Inventory events
LOG_WARNING("Low stock detected for Rice A", "Inventory");

// Database events
LOG_INFO("Transaction recorded with ID: 123", "Database");
```

## 🛠️ Troubleshooting

### Common Issues:

1. **No logs appearing**:
   - Check if the database table exists
   - Verify Arduino is connected to WiFi
   - Check the API endpoint URL in Arduino code

2. **Arduino not sending logs**:
   - Ensure WiFi connection is stable
   - Check the logsUrl constant is correct
   - Verify the sendLogToServer function is added

3. **Web interface not loading**:
   - Check if logs.php exists and is accessible
   - Verify database connection
   - Check browser console for JavaScript errors

### Testing:
1. Run `test_logging_system.php` to verify database and API
2. Check Arduino Serial Monitor for log sending messages
3. Use browser developer tools to check API responses

## 📊 API Endpoints

### POST `/arduino_logs_api.php`
Send logs from Arduino:
```json
{
  "machine_id": "rice_dispenser_1",
  "log_level": "INFO",
  "log_message": "System started",
  "log_category": "System"
}
```

### GET `/arduino_logs_api.php`
Retrieve logs for web interface:
- `?limit=100` - Number of logs to retrieve
- `?log_level=ERROR` - Filter by log level
- `?category=WiFi` - Filter by category

### DELETE `/arduino_logs_api.php`
Clear all logs (used by web interface)

## 🎨 Customization

### Styling:
- Modify CSS in `logs.php` to change colors and layout
- Log level colors can be customized in the `.log-level` classes

### Logging Behavior:
- Adjust refresh intervals in the web interface
- Modify log retention by adding cleanup scripts
- Add more log categories as needed

## 🔒 Security Notes

- The API accepts logs from any source (for Arduino compatibility)
- Consider adding authentication for production use
- Logs are stored in plain text in the database
- Consider log rotation for long-term storage

## 📈 Performance

- Logs are stored efficiently with indexed columns
- Web interface loads only recent logs by default
- Auto-refresh can be disabled to reduce server load
- Consider implementing log rotation for production use

---

**Ready to use!** Your Arduino serial monitor is now integrated into your website. You can monitor your rice dispenser's activity in real-time from anywhere with internet access.
