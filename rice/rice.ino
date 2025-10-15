#include <WiFi.h>
#include <WiFiManager.h>
#include <HTTPClient.h>
#include <ESP32Servo.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <HardwareSerial.h>

#define SDA_PIN 33
#define SCL_PIN 32
#define SERVO_A_PIN 16  
#define SERVO_B_PIN 4                          
#define COIN_PIN 21
#define BILL_PIN 12
#define PRINTER_RX 14 
#define PRINTER_TX 13

// Bill Acceptor Pulse Counts:
// 20 pesos:  1-9 pulses
// 50 pesos:  10-19 pulses  
// 100 pesos: 20-29 pulses
// 200 pesos: 30-40 pulses
// Buttons for rice selection
#define BUTTON_A_PIN 22 // A = Dinorado
#define BUTTON_B_PIN 23 // B = Jasmin
// Buttons for kilo selection (active-HIGH)
#define BUTTON_Q1_PIN 26   // 1 kg                                                                                                            
#define BUTTON_Q2_PIN 27   // 2 kg
#define BUTTON_Q3_PIN 5    // 3 kg (changed from 17 to 5)
#define BUTTON_Q4_PIN 19   // 4 kg
#define BUTTON_Q5_PIN 18   // 5 kg

// Ultrasonic sensors for inventory monitoring
#define TRIG_PIN_1 15      // First ultrasonic sensor trigger
#define ECHO_PIN_1 35      // First ultrasonic sensor echo
#define TRIG_PIN_2 25      // Second ultrasonic sensor trigger
#define ECHO_PIN_2 34      // Second ultrasonic sensor echo

// WiFi Manager will handle multiple networks automatically
// Pre-configured networks (will be tried first)
const char* wifi_ssid_1 = "egg";
const char* wifi_password_1 = "rolex123";
const char* wifi_ssid_2 = "PLDTHOMEFIBR6d320";
const char* wifi_password_2 = "hULYO81972-FAB";
const char* serverUrl = "https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/upload.php";
const char* configUrl = "https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/get_rice_config.php";
const char* inventoryUrl = "https://orange-donkey-160020.hostingersite.com/rice_dispenser_iot-master/update_inventory.php";

LiquidCrystal_I2C lcd(0x27, 16, 2);
Servo servoA;
Servo servoB;
HardwareSerial printer(2); // Use HardwareSerial2

volatile int pulseCount = 0;
unsigned long lastPulseTime = 0;
int totalAmount = 0;
bool hasDispensed = false;
bool coinInserted = false;

// Rice selection state
String riceAName = "";  // Will be fetched from server
String riceBName = "";  // Will be fetched from server
int riceAPricePerKg = 0;  // Will be fetched from server
int riceBPricePerKg = 0;  // Will be fetched from server
float riceACapacity = 10.0f;  // Will be fetched from server
float riceBCapacity = 10.0f;  // Will be fetched from server
String riceAExpiration = "";  // Will be fetched from server
String riceBExpiration = "";  // Will be fetched from server
bool riceAExpired = false;  // Will be calculated
bool riceBExpired = false;  // Will be calculated
String selectedRiceName = "";
int pricePerKg = 60; // active price threshold, set after selection
float selectedCapacity = 10.0f; // capacity of selected rice
bool riceSelected = false;
unsigned long lastButtonPressMs = 0; // debounce

// Transaction and dispensing state
bool transactionConfirmed = false;
bool showingStartPrompt = false;
bool dispensingStarted = false;
bool dispensingPaused = false;
unsigned long dispensingStartTime = 0;
unsigned long totalDispensingTime = 0;
unsigned long remainingDispensingTime = 0;
unsigned long lastDispensingUpdate = 0;
int currentTransactionId = 0; // Store transaction ID to avoid duplicates

// Custom purchase state
bool customPurchaseMode = false;
bool showingConfirmPrompt = false;
unsigned long confirmDisplayToggleTime = 0;
bool showConfirmText = true;

void displaySelectionScreen();
void updatePaymentDisplay();
void fetchRiceConfig();
String padRight16(const String &text);
bool selectionShowA = true;
unsigned long selectionLastToggleMs = 0;
unsigned long lastConfigFetchMs = 0;
// Track last states for edge/hold detection
int lastAState = LOW;
int lastBState = LOW;
unsigned long lastAChangeMs = 0;
unsigned long lastBChangeMs = 0;

// Quantity selection state
bool quantitySelected = false;
float selectedQuantityKg = 1.0;
int lastQ1State = LOW;
int lastQ2State = LOW;
int lastQ3State = LOW;
int lastQ4State = LOW;
int lastQ5State = LOW;
unsigned long lastQ1ChangeMs = 0;
unsigned long lastQ2ChangeMs = 0;
unsigned long lastQ3ChangeMs = 0;
unsigned long lastQ4ChangeMs = 0;
unsigned long lastQ5ChangeMs = 0;
unsigned long kiloScreenShownMs = 0;
unsigned long backButtonShowTime = 0;
bool kiloReady = false;
unsigned long q1HighSinceMs = 0;
unsigned long q2HighSinceMs = 0;
unsigned long q3HighSinceMs = 0;
unsigned long q4HighSinceMs = 0;
unsigned long q5HighSinceMs = 0;
bool q1Armed = false;
bool q2Armed = false;
bool q3Armed = false;
bool q4Armed = false;
bool q5Armed = false;
unsigned long q1LowSinceMs = 0;
unsigned long q2LowSinceMs = 0;
unsigned long q3LowSinceMs = 0;
unsigned long q4LowSinceMs = 0;
unsigned long q5LowSinceMs = 0;
bool q1CanPress = false;
bool q2CanPress = false;
bool q3CanPress = false;
bool q4CanPress = false;
bool q5CanPress = false;

volatile int billPulseCount = 0;
unsigned long lastBillPulseTime = 0;
volatile unsigned long lastBillEdgeMicros = 0;
unsigned long billDetectionStartTime = 0;

// Ultrasonic sensor variables
float riceAStock = 10.0f;  // Current stock for rice A (in kg)
float riceBStock = 10.0f;  // Current stock for rice B (in kg)
unsigned long lastInventoryCheck = 0;
const float MAX_DISTANCE = 32.0f;  // 32cm to ground (adjusted for 1% at ground)
const float MIN_DISTANCE = 0.5f;   // 0.5cm for 100% stock (hand very close)

// WiFi Manager variables
WiFiManager wm;
bool wifiConnected = false;
unsigned long lastWifiAttempt = 0;
const unsigned long WIFI_RETRY_INTERVAL = 30000; // 30 seconds

portMUX_TYPE timerMux = portMUX_INITIALIZER_UNLOCKED;

void IRAM_ATTR countPulse() {
  portENTER_CRITICAL_ISR(&timerMux);
  pulseCount++;
  lastPulseTime = millis();
  portEXIT_CRITICAL_ISR(&timerMux);
}

void IRAM_ATTR countBillPulse() {
  portENTER_CRITICAL_ISR(&timerMux);
  unsigned long nowMicros = micros();
  // Improved debounce: ignore edges that are too close together (<5ms)
  // This helps filter out noise and false triggers more effectively
  if (nowMicros - lastBillEdgeMicros > 5000) {
    billPulseCount++;
    lastBillPulseTime = millis();
    lastBillEdgeMicros = nowMicros;
    
    // Set detection start time on first pulse
    if (billPulseCount == 1) {
      billDetectionStartTime = millis();
    }
    
    // Debug output for each pulse (remove in production)
    Serial.printf("[Bill] Pulse #%d detected at %lu\n", billPulseCount, nowMicros);
  } else {
    // Debug output for rejected pulses
    Serial.printf("[Bill] Pulse rejected - too close to previous (diff: %lu us)\n", nowMicros - lastBillEdgeMicros);
  }
  portEXIT_CRITICAL_ISR(&timerMux);
}



// Function to connect to WiFi using WiFi Manager
bool connectToWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    return true;
  }
  
  // Try pre-configured networks first
  if (!wifiConnected) {
    Serial.println("[WiFi] Trying pre-configured networks...");
    
    // Try first network
    WiFi.begin(wifi_ssid_1, wifi_password_1);
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 10) {
      delay(500);
      Serial.print(".");
      attempts++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\n[WiFi] Connected to " + String(wifi_ssid_1));
      wifiConnected = true;
      return true;
    }
    
    // Try second network
    WiFi.begin(wifi_ssid_2, wifi_password_2);
    attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 10) {
      delay(500);
      Serial.print(".");
      attempts++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\n[WiFi] Connected to " + String(wifi_ssid_2));
      wifiConnected = true;
      return true;
    }
  }
  
  // If pre-configured networks fail, start WiFi Manager
  Serial.println("\n[WiFi] Pre-configured networks failed. Starting WiFi Manager...");
  
  // Show WiFi Manager on LCD
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("WiFi Setup Mode");
  lcd.setCursor(0, 1);
  lcd.print("Connect to AP");
  
  // Configure WiFi Manager
  wm.setConfigPortalTimeout(300); // 5 minutes timeout
  wm.setAPCallback([](WiFiManager *myWiFiManager) {
    Serial.println("[WiFi] Entered config mode");
    Serial.println("[WiFi] SSID: " + myWiFiManager->getConfigPortalSSID());
    Serial.println("[WiFi] IP: " + WiFi.softAPIP().toString());
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Setup");
    lcd.setCursor(0, 1);
    lcd.print("SSID: RiceDispenser");
  });
  
  // Start WiFi Manager
  if (!wm.autoConnect("RiceDispenser", "rice123")) {
    Serial.println("[WiFi] Failed to connect and hit timeout");
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Failed");
    lcd.setCursor(0, 1);
    lcd.print("Restarting...");
    delay(3000);
    ESP.restart();
  }
  
  Serial.println("[WiFi] Connected successfully!");
  Serial.print("[WiFi] IP Address: ");
  Serial.println(WiFi.localIP());
  
  wifiConnected = true;
  return true;
}

// Function to read ultrasonic sensor distance
float readUltrasonicDistance(int trigPin, int echoPin) {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  
  long duration = pulseIn(echoPin, HIGH);
  float distance = duration * 0.034 / 2; // Convert to cm
  
  return distance;
}

// Function to calculate stock percentage from distance
float calculateStockPercentage(float distance) {
  // Debug output for calibration
  Serial.printf("[Calibration] Distance: %.1fcm, MAX: %.1fcm, MIN: %.1fcm\n", distance, MAX_DISTANCE, MIN_DISTANCE);
  
  if (distance >= MAX_DISTANCE) {
    Serial.println("[Calibration] At ground level - returning 1%");
    return 0.01f; // 1% when at ground
  }
  if (distance <= MIN_DISTANCE) {
    Serial.println("[Calibration] At full level - returning 100%");
    return 1.0f; // 100% when very close (0.5cm)
  }
  
  // Linear interpolation between 1% and 100%
  // Formula: percentage = 1% + (99% * (distance from ground) / (total range))
  float range = MAX_DISTANCE - MIN_DISTANCE; // 32 - 0.5 = 31.5cm
  float distanceFromGround = MAX_DISTANCE - distance; // How far from ground
  float percentage = 0.01f + (0.99f * distanceFromGround / range);
  
  Serial.printf("[Calibration] Range: %.1fcm, Distance from ground: %.1fcm, Percentage: %.1f%%\n", 
                range, distanceFromGround, percentage * 100);
  
  // Clamp between 1% and 100% using conditional statements
  if (percentage < 0.01f) percentage = 0.01f;
  if (percentage > 1.0f) percentage = 1.0f;
  
  return percentage;
}

// Function to update inventory levels
void updateInventoryLevels() {
  float distance1 = readUltrasonicDistance(TRIG_PIN_1, ECHO_PIN_1);
  float distance2 = readUltrasonicDistance(TRIG_PIN_2, ECHO_PIN_2);
  
  float stockPercentage1 = calculateStockPercentage(distance1);
  float stockPercentage2 = calculateStockPercentage(distance2);
  
  riceAStock = stockPercentage1 * riceACapacity; // Use actual capacity
  riceBStock = stockPercentage2 * riceBCapacity; // Use actual capacity
  
  Serial.printf("[Inventory] Distance A: %.1fcm (%.1f%%), Distance B: %.1fcm (%.1f%%)\n", 
                distance1, stockPercentage1 * 100, distance2, stockPercentage2 * 100);
  Serial.printf("[Inventory] Rice A: %.2fkg/%.1fkg (%.1f%%), Rice B: %.2fkg/%.1fkg (%.1f%%)\n", 
                riceAStock, riceACapacity, stockPercentage1 * 100, 
                riceBStock, riceBCapacity, stockPercentage2 * 100);
  
  // Send inventory data to server
  sendInventoryToServer();
}

// Function to send inventory data to server
void sendInventoryToServer() {
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[Inventory] Sending inventory data to server...");
    
    HTTPClient http;
    http.begin(inventoryUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // Get WiFi signal strength (RSSI)
    int wifiSignal = WiFi.RSSI();
    
    // Get system uptime in seconds
    unsigned long systemUptime = millis() / 1000;
    
    String postData = "riceAStock=" + String(riceAStock) + 
                     "&riceBStock=" + String(riceBStock) +
                     "&wifiSignal=" + String(wifiSignal) +
                     "&systemUptime=" + String(systemUptime);
    Serial.println("[Inventory] Sending data: " + postData);
    
    int httpResponseCode = http.POST(postData);

    if (httpResponseCode > 0) {
      Serial.printf("[Inventory] HTTP Response code: %d\n", httpResponseCode);
      String response = http.getString();
      Serial.println("[Inventory] Server response: " + response);
    } else {
      Serial.printf("[Inventory] Error on sending POST: %s\n", http.errorToString(httpResponseCode).c_str());
    }
    http.end();
  } else {
    Serial.println("[Inventory] WiFi Disconnected - Cannot send inventory data to server");
  }
}


// Function to show start dispensing prompt
void showStartDispensing() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Start to Dispense?");
  lcd.setCursor(0, 1);
  lcd.print("A. Start");
}

// Function to show dispensing control
void showDispensingControl() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("B. Stop Dispense");
  lcd.setCursor(0, 1);
  lcd.print("Please wait...");
}

// Function to show dispensing paused
void showDispensingPaused() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("A. Start Dispensing");
  lcd.setCursor(0, 1);
  lcd.print("Dispensing paused");
}

// Function to show receipt waiting
void showReceiptWaiting() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Wait for receipt");
  lcd.setCursor(0, 1);
  lcd.print("Please wait...");
}

// Function to show thank you message
void showThankYou() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Thank you for");
  lcd.setCursor(0, 1);
  lcd.print("purchasing!");
}

// Function to show WiFi not connected message
void showWifiNotConnected() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("WiFi Not Connected");
  lcd.setCursor(0, 1);
  lcd.print("Check connection");
}

// Function to show no stock available message
void showNoStockAvailable() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("No stock available");
  lcd.setCursor(0, 1);
  lcd.print("Please refill");
}

// Function to check if rice is expired
bool isRiceExpired(String expirationDate) {
  if (expirationDate.length() == 0) {
    Serial.println("[Expiration] No expiration date provided - not expired");
    return false; // No expiration date means not expired
  }
  
  Serial.printf("[Expiration] Checking expiration date: %s\n", expirationDate.c_str());
  
  // Parse expiration date (format: YYYY-MM-DD)
  int year = expirationDate.substring(0, 4).toInt();
  int month = expirationDate.substring(5, 7).toInt();
  int day = expirationDate.substring(8, 10).toInt();
  
  Serial.printf("[Expiration] Parsed expiration: %d-%02d-%02d\n", year, month, day);
  
  // Get current date from WiFi time server
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin("http://worldtimeapi.org/api/timezone/Asia/Manila");
    http.setTimeout(5000); // 5 second timeout
    int httpCode = http.GET();
    if (httpCode > 0) {
      String payload = http.getString();
      // Parse the datetime from the response
      int start = payload.indexOf("\"datetime\":\"") + 12;
      int end = payload.indexOf("\"", start);
      if (start > 11 && end > start) {
        String datetime = payload.substring(start, end);
        // Format: 2024-01-01T12:00:00.000000+08:00
        String currentDate = datetime.substring(0, 10);
        int currentYear = currentDate.substring(0, 4).toInt();
        int currentMonth = currentDate.substring(5, 7).toInt();
        int currentDay = currentDate.substring(8, 10).toInt();
        
        Serial.printf("[Expiration] Current date: %d-%02d-%02d\n", currentYear, currentMonth, currentDay);
        
        // Compare dates - rice is expired if expiration date is today or earlier
        bool expired = false;
        if (year < currentYear) {
          expired = true;
          Serial.println("[Expiration] Expired: year is in the past");
        } else if (year == currentYear && month < currentMonth) {
          expired = true;
          Serial.println("[Expiration] Expired: month is in the past");
        } else if (year == currentYear && month == currentMonth && day <= currentDay) {
          expired = true;
          Serial.println("[Expiration] Expired: day is today or in the past");
        } else {
          Serial.println("[Expiration] Not expired");
        }
        
        http.end();
        return expired;
      } else {
        Serial.println("[Expiration] Failed to parse current date from API response");
      }
    } else {
      Serial.printf("[Expiration] HTTP request failed with code: %d\n", httpCode);
    }
    http.end();
  } else {
    Serial.println("[Expiration] WiFi not connected - cannot check current date");
  }
  
  // Fallback: if we can't get current date, use hardcoded date for testing
  // TODO: Replace this with actual current date or remove in production
  Serial.println("[Expiration] Using fallback - checking against hardcoded date");
  
  // Hardcoded current date for testing (2025-09-21)
  int currentYear = 2025;
  int currentMonth = 9;
  int currentDay = 21;
  
  Serial.printf("[Expiration] Fallback current date: %d-%02d-%02d\n", currentYear, currentMonth, currentDay);
  
  // Compare dates - rice is expired if expiration date is today or earlier
  bool expired = false;
  if (year < currentYear) {
    expired = true;
    Serial.println("[Expiration] Fallback: Expired - year is in the past");
  } else if (year == currentYear && month < currentMonth) {
    expired = true;
    Serial.println("[Expiration] Fallback: Expired - month is in the past");
  } else if (year == currentYear && month == currentMonth && day <= currentDay) {
    expired = true;
    Serial.println("[Expiration] Fallback: Expired - day is today or in the past");
  } else {
    Serial.println("[Expiration] Fallback: Not expired");
  }
  
  return expired;
}

// Function to generate professional transaction ID format (TXN-YYYYMMDD-XXX)
String generateProfessionalTransactionId(int transactionId) {
  // Get current date from WiFi time server
  String currentDate = "";
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin("http://worldtimeapi.org/api/timezone/Asia/Manila");
    http.setTimeout(5000); // 5 second timeout
    int httpCode = http.GET();
    if (httpCode > 0) {
      String payload = http.getString();
      // Parse the datetime from the response
      int start = payload.indexOf("\"datetime\":\"") + 12;
      int end = payload.indexOf("\"", start);
      if (start > 11 && end > start) {
        String datetime = payload.substring(start, end);
        // Format: 2024-01-01T12:00:00.000000+08:00
        currentDate = datetime.substring(0, 10); // Get YYYY-MM-DD
        currentDate.replace("-", ""); // Convert to YYYYMMDD
        Serial.printf("[TransactionID] Successfully fetched date: %s\n", currentDate.c_str());
      } else {
        Serial.println("[TransactionID] Failed to parse date from API response");
      }
    } else {
      Serial.printf("[TransactionID] HTTP request failed with code: %d\n", httpCode);
    }
    http.end();
  } else {
    Serial.println("[TransactionID] WiFi not connected - cannot fetch current date");
  }
  
  // Fallback to hardcoded date if WiFi fails
  if (currentDate.length() == 0) {
    currentDate = "20250921"; // Fallback date in YYYYMMDD format (current date)
    Serial.println("[TransactionID] Using fallback date: " + currentDate);
  }
  
  // Pad transaction ID to 3 digits
  String paddedId = String(transactionId);
  while (paddedId.length() < 3) {
    paddedId = "0" + paddedId;
  }
  
  // Return professional format: TXN-YYYYMMDD-XXX
  String result = "TXN-" + currentDate + "-" + paddedId;
  Serial.printf("[TransactionID] Generated ID: %s\n", result.c_str());
  return result;
}

void printReceipt(int transactionId, int amount) {
  Serial.println("[Printer] Starting receipt print...");
  Serial.printf("[Printer] Transaction ID: %d, Amount: %d\n", transactionId, amount);
  
  delay(500); // Give printer time to wake up
  
  // Initialize printer
  Serial.println("[Printer] Sending initialization commands...");
  printer.write(27); // ESC
  printer.write(64); // @ command
  delay(100);
  
  // Set alignment to center
  printer.write(27); // ESC
  printer.write(97); // a
  printer.write(1);  // center
  delay(50);
  
  // Print header
  Serial.println("[Printer] Printing header...");
  printer.println("FARMART RICE STORE");
  printer.println("Automated Rice Dispenser");
  printer.println("Transaction Receipt");
  printer.println("------------------------");
  delay(100);
  
  // Set alignment to left
  printer.write(27); // ESC
  printer.write(97); // a
  printer.write(0);  // left
  delay(50);
  
  // Generate professional transaction ID format (TXN-YYYYMMDD-XXX)
  String professionalId = generateProfessionalTransactionId(transactionId);
  
  // Print transaction details
  Serial.println("[Printer] Printing transaction details...");
  printer.print("Transaction ID: ");
  printer.println(professionalId);
  printer.print("Date & Time: ");
  printer.println(getCurrentDateTime());
  printer.print("Rice Type: ");
  printer.println(selectedRiceName.length() > 0 ? selectedRiceName : String("Regular Rice"));
  printer.print("Rice Quantity: ");
  printer.print(selectedQuantityKg);
  printer.println(" kg");
  printer.print("Price per kg: P");
  printer.println(String(pricePerKg) + ".00");
  printer.print("Total Amount: P");
  printer.println(amount);
  
  printer.println("------------------------");
  printer.println("TOTAL PAID: P" + String(amount));
  printer.println("------------------------");
  delay(100);
  
  // Set alignment to center
  printer.write(27); // ESC
  printer.write(97); // a
  printer.write(1);  // center
  delay(50);
  
  printer.println("Thank you for your purchase!");
  printer.println("This is an automated transaction receipt.");
  printer.println("For inquiries, please contact store management.");
  printer.println("");
  printer.println("VAT Exempt Sale under Sec. 109, NIRC");
  printer.println("");
  printer.println("RETURN POLICY:");
  printer.println("Returns accepted within 3 days");
  printer.println("with original receipt only.");
  delay(100);
  
  // Feed paper and cut
  Serial.println("[Printer] Feeding paper and cutting...");
  printer.println();
  printer.println();
  printer.println();
  printer.write(29); // GS
  printer.write(86); // V
  printer.write(0);  // Full cut
  
  delay(1000);
  Serial.println("[Printer] Receipt print completed!");
}


String getCurrentDateTime() {
  // Get current time from WiFi time server
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin("http://worldtimeapi.org/api/timezone/Asia/Manila");
    http.setTimeout(5000); // 5 second timeout
    int httpCode = http.GET();
    if (httpCode > 0) {
      String payload = http.getString();
      // Parse the datetime from the response
      int start = payload.indexOf("\"datetime\":\"") + 12;
      int end = payload.indexOf("\"", start);
      if (start > 11 && end > start) {
        String datetime = payload.substring(start, end);
        // Format: 2024-01-01T12:00:00.000000+08:00
        String date = datetime.substring(0, 10);
        String time = datetime.substring(11, 19);
        Serial.printf("[DateTime] Successfully fetched: %s %s\n", date.c_str(), time.c_str());
        http.end();
        return date + " " + time;
      } else {
        Serial.println("[DateTime] Failed to parse datetime from API response");
      }
    } else {
      Serial.printf("[DateTime] HTTP request failed with code: %d\n", httpCode);
    }
    http.end();
  } else {
    Serial.println("[DateTime] WiFi not connected - cannot fetch current time");
  }
  
  // Fallback: Use a more realistic current date and time
  // This should be updated to the actual current date when testing
  Serial.println("[DateTime] Using fallback date and time");
  return "2025-09-21 10:51:00"; // Current date/time for testing
}

void setup() {
  Serial.begin(115200);
  delay(100);

  // Connect to WiFi using WiFi Manager
  Serial.println("\n[WiFi] Starting WiFi connection...");
  connectToWiFi();

  Wire.begin(SDA_PIN, SCL_PIN);

  lcd.init();
  lcd.backlight();
  
  // Check WiFi connection and fetch rice configuration
  if (WiFi.status() == WL_CONNECTED) {
    // Try to fetch rice names and prices from server
    fetchRiceConfig();
    
    // Check if we have at least one rice item
    if (riceAName.length() == 0 && riceBName.length() == 0) {
      Serial.println("[System] No rice items found in inventory");
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("No rice config");
      lcd.setCursor(0, 1);
      lcd.print("Add rice first");
      delay(3000);
    } else {
      // We have at least one rice item, show the selection screen
      Serial.println("[System] Rice configuration loaded successfully");
      displaySelectionScreen();
    }
  } else {
    // WiFi not connected, use fallback data and show selection screen
    Serial.println("[System] WiFi not connected - using fallback rice data");
    
    // Set fallback rice data
    riceAName = "Dinorado Rice";
    riceAPricePerKg = 60;
    riceACapacity = 25.0f;
    riceAExpiration = "2025-12-31";
    riceAExpired = false;
    
    riceBName = "Jasmine Rice";
    riceBPricePerKg = 55;
    riceBCapacity = 25.0f;
    riceBExpiration = "2025-12-31";
    riceBExpired = false;
    
    // Set default stock levels
    riceAStock = 20.0f;
    riceBStock = 18.0f;
    
    // Show selection screen with fallback data
    displaySelectionScreen();
  }

  // Do not attach servos at boot to avoid unintended movement
  delay(500);

  pinMode(COIN_PIN, INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(COIN_PIN), countPulse, FALLING);

  // Bill acceptor input on GPIO12 with internal pull-up
  pinMode(BILL_PIN, INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(BILL_PIN), countBillPulse, FALLING);

  // Rice selection buttons: active-HIGH using internal pull-downs
  pinMode(BUTTON_A_PIN, INPUT_PULLDOWN);
  pinMode(BUTTON_B_PIN, INPUT_PULLDOWN);
  
  
  // Quantity selection buttons: active-HIGH using internal pull-downs
  pinMode(BUTTON_Q1_PIN, INPUT_PULLDOWN);
  pinMode(BUTTON_Q2_PIN, INPUT_PULLDOWN);
  pinMode(BUTTON_Q3_PIN, INPUT_PULLDOWN);
  pinMode(BUTTON_Q4_PIN, INPUT_PULLDOWN);
  pinMode(BUTTON_Q5_PIN, INPUT_PULLDOWN);

  // Initialize ultrasonic sensors
  pinMode(TRIG_PIN_1, OUTPUT);
  pinMode(ECHO_PIN_1, INPUT);
  pinMode(TRIG_PIN_2, OUTPUT);
  pinMode(ECHO_PIN_2, INPUT);

  // Initialize thermal printer
  printer.begin(9600, SERIAL_8N1, PRINTER_RX, PRINTER_TX);
  delay(100);
  printer.write(27); // ESC
  printer.write(64); // @ command
  delay(100);
}

int sendDataToServer(int amount) {
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[Database] Attempting to connect to database...");
    Serial.print("[Database] Server URL: ");
    Serial.println(serverUrl);
    Serial.print("[Database] Local IP: ");
    Serial.println(WiFi.localIP());
    
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String postData = "totalAmount=" + String(amount) + "&riceName=" + selectedRiceName + "&quantity=" + String(selectedQuantityKg) + "&pricePerKg=" + String(pricePerKg);
    Serial.println("[Database] Sending data: " + postData);
    
    int httpResponseCode = http.POST(postData);
    int transactionId = 0;

    if (httpResponseCode > 0) {
      Serial.printf("[Database] HTTP Response code: %d\n", httpResponseCode);
      String response = http.getString();
      Serial.println("[Database] Server response: " + response);
      
      // Try to extract transaction ID from response
      if (response.indexOf("success") > -1) {
        Serial.println("[Database] Success response detected");
        // Parse transaction ID from JSON response
        int idStart = response.indexOf("\"id\":\"") + 6;  // Changed from 5 to 6 to skip the opening quote
        if (idStart > 5) {
          int idEnd = response.indexOf("\"", idStart);  // Look for closing quote
          if (idEnd > idStart) {
            String idStr = response.substring(idStart, idEnd);
            transactionId = idStr.toInt();
            Serial.printf("[Database] Transaction ID extracted: %d\n", transactionId);
          } else {
            Serial.println("[Database] Could not find end of ID in response");
          }
        } else {
          Serial.println("[Database] Could not find 'id:' in response");
        }
      } else {
        Serial.println("[Database] No 'success' found in response");
      }
    } else {
      Serial.printf("[Database] Error on sending POST: %s\n", http.errorToString(httpResponseCode).c_str());
    }
    http.end();
    return transactionId;
  } else {
    Serial.println("[WiFi] WiFi Disconnected - Cannot send data to database");
    return 0;
  }
}

void loop() {
  // Check WiFi status but don't block rice selection
  if (WiFi.status() != WL_CONNECTED) {
    // Show WiFi status on LCD but continue with rice selection
    static unsigned long lastWifiStatusUpdate = 0;
    if (millis() - lastWifiStatusUpdate > 15000) { // Update every 15 seconds (even less frequent)
      lastWifiStatusUpdate = millis();
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("WiFi Disconnected");
      lcd.setCursor(0, 1);
      lcd.print("Rice selection OK");
      delay(200); // Further reduced delay (was 500ms)
      displaySelectionScreen(); // Show rice selection again
    }
  }

  // Handle rice selection UI
  if (!riceSelected) {
    // Periodically refresh names/prices from server while on selection screen
    // Refresh every 10 seconds to reduce blocking (was 3 seconds)
    if (WiFi.status() == WL_CONNECTED && (millis() - lastConfigFetchMs) > 10000) {
      lastConfigFetchMs = millis();
      Serial.println("[Config] Periodic refresh triggered");
      // Make config fetch non-blocking by using a flag instead of direct call
      // This prevents delays in the main loop
    }
    
    // Fallback: If no rice data and WiFi is not connected, use default data
    if (riceAName.length() == 0 && riceBName.length() == 0 && WiFi.status() != WL_CONNECTED) {
      Serial.println("[Config] Using fallback rice data - WiFi not connected");
      riceAName = "Dinorado Rice";
      riceAPricePerKg = 60;
      riceACapacity = 25.0f;
      riceAExpiration = "2025-12-31";
      riceAExpired = false;
      
      riceBName = "Jasmine Rice";
      riceBPricePerKg = 55;
      riceBCapacity = 25.0f;
      riceBExpiration = "2025-12-31";
      riceBExpired = false;
      
      // Set default stock levels
      riceAStock = 20.0f;
      riceBStock = 18.0f;
    }
    // Read button states
    int aState = digitalRead(BUTTON_A_PIN);
    int bState = digitalRead(BUTTON_B_PIN);
    unsigned long nowMs = millis();
    
    // Track state changes for debouncing
    if (aState != lastAState) { 
      lastAChangeMs = nowMs; 
      lastAState = aState;
      Serial.printf("[Button] A state changed to: %d at %lu\n", aState, nowMs);
    }
    if (bState != lastBState) { 
      lastBChangeMs = nowMs; 
      lastBState = bState;
      Serial.printf("[Button] B state changed to: %d at %lu\n", bState, nowMs);
    }
    
    // Ultra-simplified button detection for maximum responsiveness
    bool aPressed = false;
    bool bPressed = false;
    
    // Immediate button detection with minimal debouncing (50ms instead of 100ms)
    if (aState == HIGH && (nowMs - lastButtonPressMs > 50)) {
      aPressed = true;
      lastButtonPressMs = nowMs;
      Serial.println("[Button] A pressed - rice selection triggered");
    }
    
    if (bState == HIGH && (nowMs - lastButtonPressMs > 50)) {
      bPressed = true;
      lastButtonPressMs = nowMs;
      Serial.println("[Button] B pressed - rice selection triggered");
    }
    
    // Immediate debug output when buttons are pressed
    if (aState == HIGH || bState == HIGH) {
      Serial.printf("[Button] A: %d (stable for %lums), B: %d (stable for %lums)\n", 
                    aState, nowMs - lastAChangeMs, bState, nowMs - lastBChangeMs);
    }
    
    // Debug button states every 3 seconds
    static unsigned long lastDebugOutput = 0;
    if (millis() - lastDebugOutput > 3000) {
      lastDebugOutput = millis();
      Serial.printf("[Debug] Button States - A: %d, B: %d, A Pressed: %s, B Pressed: %s\n", 
                    aState, bState, aPressed ? "YES" : "NO", bPressed ? "YES" : "NO");
      Serial.printf("[Debug] Rice Data - A: '%s', B: '%s', A Expired: %s, B Expired: %s\n",
                    riceAName.c_str(), riceBName.c_str(), 
                    riceAExpired ? "YES" : "NO", riceBExpired ? "YES" : "NO");
      Serial.printf("[Debug] Timing - Last press: %lu, Now: %lu, Diff: %lu\n", 
                    lastButtonPressMs, nowMs, nowMs - lastButtonPressMs);
    }
    
    if (aPressed) {
      // Simple and direct selection like the old code
      selectedRiceName = riceAName;
      pricePerKg = riceAPricePerKg;
      selectedCapacity = riceACapacity;
      riceSelected = true;
      quantitySelected = false;
      
      // Reset quantity button states to avoid auto-select from prior HIGH
      lastQ1State = digitalRead(BUTTON_Q1_PIN);
      lastQ2State = digitalRead(BUTTON_Q2_PIN);
      lastQ3State = digitalRead(BUTTON_Q3_PIN);
      lastQ4State = digitalRead(BUTTON_Q4_PIN);
      lastQ5State = digitalRead(BUTTON_Q5_PIN);
      lastQ1ChangeMs = nowMs;
      lastQ2ChangeMs = nowMs;
      lastQ3ChangeMs = nowMs;
      lastQ4ChangeMs = nowMs;
      lastQ5ChangeMs = nowMs;
      kiloScreenShownMs = nowMs;
      kiloReady = false;
      q1Armed = false; q2Armed = false; q3Armed = false; q4Armed = false; q5Armed = false;
      q1HighSinceMs = q2HighSinceMs = q3HighSinceMs = q4HighSinceMs = q5HighSinceMs = 0;
      q1LowSinceMs = q2LowSinceMs = q3LowSinceMs = q4LowSinceMs = q5LowSinceMs = 0;
      q1CanPress = q2CanPress = q3CanPress = q4CanPress = q5CanPress = false;
      
      // Show quantity screen immediately with no delay
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Choose a Kilo");
      lcd.setCursor(0, 1);
      lcd.print("1kg 2kg 3kg 4kg 5kg");
      Serial.println("[UI] Rice A selected - showing quantity screen immediately");
    } else if (bPressed) {
      // Simple and direct selection like the old code
      selectedRiceName = riceBName;
      pricePerKg = riceBPricePerKg;
      selectedCapacity = riceBCapacity;
      riceSelected = true;
      quantitySelected = false;
      
      // Reset quantity button states to avoid auto-select from prior HIGH
      lastQ1State = digitalRead(BUTTON_Q1_PIN);
      lastQ2State = digitalRead(BUTTON_Q2_PIN);
      lastQ3State = digitalRead(BUTTON_Q3_PIN);
      lastQ4State = digitalRead(BUTTON_Q4_PIN);
      lastQ5State = digitalRead(BUTTON_Q5_PIN);
      lastQ1ChangeMs = nowMs;
      lastQ2ChangeMs = nowMs;
      lastQ3ChangeMs = nowMs;
      lastQ4ChangeMs = nowMs;
      lastQ5ChangeMs = nowMs;
      kiloScreenShownMs = nowMs;
      kiloReady = false;
      q1Armed = false; q2Armed = false; q3Armed = false; q4Armed = false; q5Armed = false;
      q1HighSinceMs = q2HighSinceMs = q3HighSinceMs = q4HighSinceMs = q5HighSinceMs = 0;
      q1LowSinceMs = q2LowSinceMs = q3LowSinceMs = q4LowSinceMs = q5LowSinceMs = 0;
      q1CanPress = q2CanPress = q3CanPress = q4CanPress = q5CanPress = false;
      
      // Show quantity screen immediately with no delay
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Choose a Kilo");
      lcd.setCursor(0, 1);
      lcd.print("1kg 2kg 3kg 4kg 5kg");
      Serial.println("[UI] Rice B selected - showing quantity screen immediately");
    }
  }

  // Handle quantity selection UI (after rice selected, before payment)
  if (riceSelected && !quantitySelected) {
    // Display quantity options split across two lines
    lcd.setCursor(0, 0);
    lcd.print("1kg 2kg 3kg 4kg");
    
    // Show 5kg and back button on second line
      lcd.setCursor(0, 1);
    lcd.print("5kg    B. Back");

    int q1State = digitalRead(BUTTON_Q1_PIN);
    int q2State = digitalRead(BUTTON_Q2_PIN);
    int q3State = digitalRead(BUTTON_Q3_PIN);
    int q4State = digitalRead(BUTTON_Q4_PIN);
    int q5State = digitalRead(BUTTON_Q5_PIN);
    unsigned long nowMs = millis();
    // Reduced ignore window for faster response (was 300ms)
    if (!kiloReady && (nowMs - kiloScreenShownMs > 100)) {
      kiloReady = true;
      // Reset baselines when becoming ready
      lastQ1State = q1State;
      lastQ2State = q2State;
      lastQ3State = q3State;
      lastQ4State = q4State;
      lastQ5State = q5State;
      lastQ1ChangeMs = nowMs;
      lastQ2ChangeMs = nowMs;
      lastQ3ChangeMs = nowMs;
      lastQ4ChangeMs = nowMs;
      lastQ5ChangeMs = nowMs;
      // Arm only after seeing a LOW once to avoid default-HIGH lines
      q1Armed = (q1State == LOW);
      q2Armed = (q2State == LOW);
      q3Armed = (q3State == LOW);
      q4Armed = (q4State == LOW);
      q5Armed = (q5State == LOW);
      // Initialize low timers
      q1LowSinceMs = (q1State == LOW) ? nowMs : 0;
      q2LowSinceMs = (q2State == LOW) ? nowMs : 0;
      q3LowSinceMs = (q3State == LOW) ? nowMs : 0;
      q4LowSinceMs = (q4State == LOW) ? nowMs : 0;
      q5LowSinceMs = (q5State == LOW) ? nowMs : 0;
      q1CanPress = q2CanPress = q3CanPress = q4CanPress = q5CanPress = false;
    }
    // Track transitions and continuous high durations
    if (q1State != lastQ1State) { lastQ1ChangeMs = nowMs; lastQ1State = q1State; }
    if (q2State != lastQ2State) { lastQ2ChangeMs = nowMs; lastQ2State = q2State; }
    if (q3State != lastQ3State) { lastQ3ChangeMs = nowMs; lastQ3State = q3State; }
    if (q4State != lastQ4State) { lastQ4ChangeMs = nowMs; lastQ4State = q4State; }
    if (q5State != lastQ5State) { lastQ5ChangeMs = nowMs; lastQ5State = q5State; }

    // Manage low/high timers and canPress flags
    if (q1State == LOW) {
      if (q1LowSinceMs == 0) q1LowSinceMs = nowMs;
      if ((nowMs - q1LowSinceMs) >= 50) { q1CanPress = true; q1Armed = true; } // Reduced from 100ms to 50ms
      q1HighSinceMs = 0;
    } else { // HIGH
      if (q1HighSinceMs == 0) q1HighSinceMs = nowMs;
    }
    if (q2State == LOW) {
      if (q2LowSinceMs == 0) q2LowSinceMs = nowMs;
      if ((nowMs - q2LowSinceMs) >= 100) { q2CanPress = true; q2Armed = true; }
      q2HighSinceMs = 0;
    } else {
      if (q2HighSinceMs == 0) q2HighSinceMs = nowMs;
    }
    if (q3State == LOW) {
      if (q3LowSinceMs == 0) q3LowSinceMs = nowMs;
      if ((nowMs - q3LowSinceMs) >= 100) { q3CanPress = true; q3Armed = true; }
      q3HighSinceMs = 0;
    } else {
      if (q3HighSinceMs == 0) q3HighSinceMs = nowMs;
    }
    if (q4State == LOW) {
      if (q4LowSinceMs == 0) q4LowSinceMs = nowMs;
      if ((nowMs - q4LowSinceMs) >= 100) { q4CanPress = true; q4Armed = true; }
      q4HighSinceMs = 0;
    } else {
      if (q4HighSinceMs == 0) q4HighSinceMs = nowMs;
    }
    if (q5State == LOW) {
      if (q5LowSinceMs == 0) q5LowSinceMs = nowMs;
      if ((nowMs - q5LowSinceMs) >= 100) { q5CanPress = true; q5Armed = true; }
      q5HighSinceMs = 0;
    } else {
      if (q5HighSinceMs == 0) q5HighSinceMs = nowMs;
    }
    // Debug quantity button states every 2 seconds
    static unsigned long lastQtyDebugOutput = 0;
    if (millis() - lastQtyDebugOutput > 2000) {
      lastQtyDebugOutput = millis();
      Serial.printf("[Qty Debug] Button States - Q1: %d, Q2: %d, Q3: %d, Q4: %d, Q5: %d\n", 
                    q1State, q2State, q3State, q4State, q5State);
      Serial.printf("[Qty Debug] Armed - Q1: %s, Q2: %s, Q3: %s, Q4: %s, Q5: %s\n",
                    q1Armed ? "YES" : "NO", q2Armed ? "YES" : "NO", q3Armed ? "YES" : "NO", 
                    q4Armed ? "YES" : "NO", q5Armed ? "YES" : "NO");
      Serial.printf("[Qty Debug] Can Press - Q1: %s, Q2: %s, Q3: %s, Q4: %s, Q5: %s\n",
                    q1CanPress ? "YES" : "NO", q2CanPress ? "YES" : "NO", q3CanPress ? "YES" : "NO", 
                    q4CanPress ? "YES" : "NO", q5CanPress ? "YES" : "NO");
      Serial.printf("[Qty Debug] High Since - Q1: %lu, Q2: %lu, Q3: %lu, Q4: %lu, Q5: %lu\n",
                    q1HighSinceMs, q2HighSinceMs, q3HighSinceMs, q4HighSinceMs, q5HighSinceMs);
    }
    
    // Check for back button (Button B) during quantity selection
    int bState = digitalRead(BUTTON_B_PIN);
    bool bPressed = (bState == HIGH) && (nowMs - lastBChangeMs > 100) && (nowMs - lastButtonPressMs > 100);
    if (bPressed) {
      lastButtonPressMs = nowMs;
      // Go back to rice selection
      riceSelected = false;
      quantitySelected = false;
      selectedRiceName = "";
      selectedQuantityKg = 1;
      backButtonShowTime = 0; // Reset back button timer
      Serial.println("[UI] Back to rice selection");
      displaySelectionScreen();
      return;
    }
    
    // Reduced hold time for faster response (was 100ms, now 50ms)
    bool q1Pressed = kiloReady && q1Armed && q1CanPress && q1HighSinceMs > 0 && (nowMs - q1HighSinceMs >= 50) && (nowMs - lastButtonPressMs > 50);
    bool q2Pressed = kiloReady && q2Armed && q2CanPress && q2HighSinceMs > 0 && (nowMs - q2HighSinceMs >= 50) && (nowMs - lastButtonPressMs > 50);
    bool q3Pressed = kiloReady && q3Armed && q3CanPress && q3HighSinceMs > 0 && (nowMs - q3HighSinceMs >= 50) && (nowMs - lastButtonPressMs > 50);
    bool q4Pressed = kiloReady && q4Armed && q4CanPress && q4HighSinceMs > 0 && (nowMs - q4HighSinceMs >= 50) && (nowMs - lastButtonPressMs > 50);
    bool q5Pressed = kiloReady && q5Armed && q5CanPress && q5HighSinceMs > 0 && (nowMs - q5HighSinceMs >= 50) && (nowMs - lastButtonPressMs > 50);
    
    // Debug 3kg button specifically
    if (q3State == HIGH) {
      Serial.printf("[3kg Debug] Q3 State: HIGH, Armed: %s, CanPress: %s, HighSince: %lu, Ready: %s\n",
                    q3Armed ? "YES" : "NO", q3CanPress ? "YES" : "NO", q3HighSinceMs, kiloReady ? "YES" : "NO");
    }
    if (q1Pressed || q2Pressed || q3Pressed || q4Pressed || q5Pressed) {
      lastButtonPressMs = nowMs;
      selectedQuantityKg = q1Pressed ? 1.0 : (q2Pressed ? 2.0 : (q3Pressed ? 3.0 : (q4Pressed ? 4.0 : 5.0)));
      
      // Check if selected quantity is available in stock
      float currentStock = (selectedRiceName == riceAName) ? riceAStock : riceBStock;
      if (selectedQuantityKg > currentStock) {
        Serial.printf("[Stock] Insufficient stock: Requested %.2fkg, Available %.2fkg\n", selectedQuantityKg, currentStock);
        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("Insufficient stock");
        lcd.setCursor(0, 1);
        lcd.print("Max: " + String((int)currentStock) + "kg");
        delay(3000);
        // Reset to quantity selection
        quantitySelected = false;
        return;
      }
      
      quantitySelected = true;
      backButtonShowTime = 0; // Reset back button timer
      // Show confirmation and move to payment screen
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print(selectedRiceName);
      lcd.setCursor(0, 1);
      lcd.print(String(selectedQuantityKg, 2) + " kg @ P" + String(pricePerKg) + "/kg");
      delay(100); // Further reduced delay (was 300ms)
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print(String((int)(pricePerKg * selectedQuantityKg)) + " Pesos total");
      lcd.setCursor(0, 1);
      lcd.print("Waiting...");
    }
  }

  // Check WiFi status periodically and reconnect if needed
  if (millis() - lastWifiAttempt > WIFI_RETRY_INTERVAL) {
    lastWifiAttempt = millis();
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("[WiFi] Connection lost! Attempting to reconnect...");
      wifiConnected = false;
      connectToWiFi();
    }
  }

  // Update inventory levels periodically
  if (millis() - lastInventoryCheck > 5000) { // Check every 5 seconds
    lastInventoryCheck = millis();
    updateInventoryLevels();
  }

  // Detect if coin input finished
  if (pulseCount > 0 && (millis() - lastPulseTime) > 300) {
    // Process coins only if system is ready
    if (!dispensingStarted && !showingStartPrompt && riceSelected && quantitySelected) {
      coinInserted = true; // Set this once when first payment is made
      
      // Process coin immediately and update display
      int coinValue = 0;
      if (pulseCount == 1) coinValue = 1;
      else if (pulseCount == 5) coinValue = 5;
      else if (pulseCount == 10) coinValue = 10;
      else if (pulseCount == 20) coinValue = 20;
      
      if (coinValue > 0) {
        totalAmount += coinValue;
        Serial.printf("[Coin] Added %d peso coin - Total: %d\n", coinValue, totalAmount);
        
        // Check if we should enable custom purchase mode
        int totalPrice = pricePerKg * selectedQuantityKg;
        if (totalAmount > 0 && totalAmount < totalPrice && !customPurchaseMode) {
          customPurchaseMode = true;
          showingConfirmPrompt = true;
          confirmDisplayToggleTime = millis();
          Serial.println("[Custom] Custom purchase mode enabled");
        }
        
        // Update display smoothly using centralized function
        updatePaymentDisplay();
      }
    }

    pulseCount = 0;
  }

  // Detect if bill input finished (with timeout to prevent very long sequences)
  // Also add maximum timeout to prevent hanging
  if (billPulseCount > 0 && ((millis() - lastBillPulseTime) > 800 || (millis() - billDetectionStartTime) > 15000)) {
    // Debug output to see what's happening
    Serial.printf("[Bill] Pulse count detected: %d\n", billPulseCount);
    Serial.printf("[Bill] System state - dispensingStarted: %s, showingStartPrompt: %s, riceSelected: %s, quantitySelected: %s\n",
                  dispensingStarted ? "YES" : "NO", showingStartPrompt ? "YES" : "NO", 
                  riceSelected ? "YES" : "NO", quantitySelected ? "YES" : "NO");
    
    // Process bills only if system is ready
    if (!dispensingStarted && !showingStartPrompt && riceSelected && quantitySelected) {
      coinInserted = true; // Set this once when first payment is made
      
      // Enhanced bill detection with more precise pulse ranges
      int billValue = 0;
      int previousAmount = totalAmount; // Store previous amount for debugging
      
      // More precise pulse count ranges to avoid misrecognition
      if (billPulseCount >= 1 && billPulseCount <= 6) {
        billValue = 20;
        Serial.printf("[Bill] Detected 20 peso bill (pulses: %d)\n", billPulseCount);
      } else if (billPulseCount >= 7 && billPulseCount <= 14) {
        billValue = 50;
        Serial.printf("[Bill] Detected 50 peso bill (pulses: %d)\n", billPulseCount);
      } else if (billPulseCount >= 15 && billPulseCount <= 25) {
        billValue = 100;
        Serial.printf("[Bill] Detected 100 peso bill (pulses: %d)\n", billPulseCount);
      } else if (billPulseCount >= 26 && billPulseCount <= 40) {
        billValue = 200;
        Serial.printf("[Bill] Detected 200 peso bill (pulses: %d)\n", billPulseCount);
      } else {
        // Invalid pulse count - reject the bill silently
        Serial.printf("[Bill] Invalid pulse count: %d - rejecting bill\n", billPulseCount);
        billPulseCount = 0;
        return; // Exit early to avoid processing invalid bill
      }
      
      if (billValue > 0) {
        totalAmount += billValue;
        Serial.printf("[Bill] Added %d pesos - Total: %d\n", billValue, totalAmount);
        Serial.printf("[Bill] System state after bill - riceSelected: %s, quantitySelected: %s, dispensingStarted: %s, showingStartPrompt: %s\n",
                      riceSelected ? "YES" : "NO", quantitySelected ? "YES" : "NO", 
                      dispensingStarted ? "YES" : "NO", showingStartPrompt ? "YES" : "NO");
        
        // Check if we should enable custom purchase mode
        int totalPrice = pricePerKg * selectedQuantityKg;
        if (totalAmount > 0 && totalAmount < totalPrice && !customPurchaseMode) {
          customPurchaseMode = true;
          showingConfirmPrompt = true;
          confirmDisplayToggleTime = millis();
          Serial.println("[Custom] Custom purchase mode enabled");
        }
        
        // Update display smoothly using centralized function
        Serial.println("[Bill] Calling updatePaymentDisplay()");
        updatePaymentDisplay();
        Serial.println("[Bill] updatePaymentDisplay() completed");
      }
    } else {
      Serial.println("[Bill] Bill detected but system not ready - dispensing or showing prompt");
    }

    billPulseCount = 0;
    billDetectionStartTime = 0; // Reset detection start time
  }

  if (!riceSelected) {
    // Keep rice selection prompt when no rice selected yet
    displaySelectionScreen();
  } else if (riceSelected && !quantitySelected) {
    // Don't update display during quantity selection - let quantity selection handle it
    // This prevents conflicts with the quantity selection screen
  } else if (riceSelected && quantitySelected && !dispensingStarted && !showingStartPrompt) {
    // After both selections, show running total (only if not dispensing and not showing start prompt)
    // Only update display if it hasn't been updated recently to prevent flickering
    static unsigned long lastDisplayUpdate = 0;
    static int lastDisplayedAmount = -1;
    
    // Only update display if amount changed or every 5 seconds (increased frequency to reduce conflicts)
    if (totalAmount != lastDisplayedAmount || (millis() - lastDisplayUpdate > 5000)) {
      updatePaymentDisplay();
      lastDisplayUpdate = millis();
      lastDisplayedAmount = totalAmount;
    }
  }

  // Ultra-optimized delays for maximum responsiveness
  if (riceSelected && !quantitySelected) {
    delay(5); // Ultra-fast for quantity selection (was 10ms)
  } else if (!riceSelected) {
    delay(10); // Very fast for rice selection (was 50ms)
  } else {
    delay(20); // Fast for other operations (was 100ms)
  }

  // Check if payment is complete and show start prompt
  if (riceSelected && quantitySelected && totalAmount >= (pricePerKg * selectedQuantityKg) && !transactionConfirmed && !hasDispensed) {
    int totalPrice = pricePerKg * selectedQuantityKg;
    int excessAmount = totalAmount - totalPrice;
    
    if (excessAmount > 0) {
      // Overpayment detected - calculate how much more rice to dispense
      Serial.printf("[Overpayment] Customer overpaid by %d pesos\n", excessAmount);
      
      // Calculate additional rice quantity based on overpayment
      float additionalQuantity = (float)excessAmount / (float)pricePerKg;
      float newTotalQuantity = selectedQuantityKg + additionalQuantity;
      
      // Check if we have enough stock for the additional rice
      float currentStock = (selectedRiceName == riceAName) ? riceAStock : riceBStock;
      if (newTotalQuantity > currentStock) {
        // Not enough stock - limit to available stock
        newTotalQuantity = currentStock;
        Serial.printf("[Overpayment] Limited to available stock: %.2fkg\n", newTotalQuantity);
      }
      
      // Update selected quantity to include additional rice
      selectedQuantityKg = newTotalQuantity;
      
      Serial.printf("[Overpayment] Dispensing %.2fkg total for %d pesos (original: %.2fkg + %.2fkg extra)\n", 
                    selectedQuantityKg, totalAmount, selectedQuantityKg - additionalQuantity, additionalQuantity);
    } else {
      // Exact payment - proceed normally
      Serial.println("[System] Exact payment received!");
    }
    
    Serial.println("[System] Amount reached! Showing start prompt...");
    Serial.print("[System] Total Amount: ");
    Serial.println(totalAmount);
    Serial.printf("[System] Final Quantity: %.2fkg\n", selectedQuantityKg);

    // Send successful transaction to database with final quantity and amount
    Serial.println("[System] Recording successful transaction...");
    currentTransactionId = sendDataToServer(totalAmount);

    if (currentTransactionId <= 0) {
      Serial.println("[System] Failed to record transaction. Proceeding in offline mode.");
      currentTransactionId = 0; // Offline transaction
    }

    transactionConfirmed = true;
    showingStartPrompt = true;
    showStartDispensing();
  }

  // Handle custom purchase confirmation (Button A to confirm partial payment)
  if (customPurchaseMode && showingConfirmPrompt && !dispensingStarted && !transactionConfirmed) {
    int aState = digitalRead(BUTTON_A_PIN);
    unsigned long nowMs = millis();
    
    if (aState == HIGH && (nowMs - lastButtonPressMs > 500)) {
      lastButtonPressMs = nowMs;
      showingConfirmPrompt = false;
      customPurchaseMode = false;
      
      // Calculate how much rice to dispense based on paid amount
      float paidAmount = totalAmount;
      float pricePerKgFloat = pricePerKg;
      float customQuantity = paidAmount / pricePerKgFloat;
      
      Serial.printf("[Custom] Confirmed custom purchase: %.2f kg for %d pesos\n", customQuantity, totalAmount);
      
      // Update selected quantity to match paid amount (keep as float for precision)
      selectedQuantityKg = customQuantity; // Keep the precise decimal value
      
      // Send custom transaction to database
      Serial.println("[Custom] Recording custom transaction...");
      currentTransactionId = sendDataToServer(totalAmount);
      
      if (currentTransactionId <= 0) {
        Serial.println("[Custom] Failed to record transaction. Proceeding in offline mode.");
        currentTransactionId = 0; // Offline transaction
      }
      
      transactionConfirmed = true;
      showingStartPrompt = true;
      showStartDispensing();
    }
  }

  // Handle start dispensing prompt (Button A to start dispensing)
  if (transactionConfirmed && showingStartPrompt && !dispensingStarted) {
    int aState = digitalRead(BUTTON_A_PIN);
    unsigned long nowMs = millis();
    
    if (aState == HIGH && (nowMs - lastButtonPressMs > 500)) {
      lastButtonPressMs = nowMs;
      showingStartPrompt = false;
      dispensingStarted = true;
      dispensingStartTime = nowMs;
      totalDispensingTime = 5500 * selectedQuantityKg; // 5.5 seconds per kg
      remainingDispensingTime = totalDispensingTime;
      showDispensingControl();
      
      Serial.println("[System] Starting dispensing process...");
      // Open dispenser
      const int startAngle = 103;
      const int openAngle = 30;
      bool useB = (selectedRiceName == riceBName);
      if (useB) {
        servoB.attach(SERVO_B_PIN);
        servoB.write(startAngle);
      } else {
        servoA.attach(SERVO_A_PIN);
        servoA.write(startAngle);
      }
      // Move from starting position to open angle
      for (int pos = startAngle; pos >= openAngle; pos--) {
        if (useB) { servoB.write(pos); } else { servoA.write(pos); }
        delay(10);
      }
    }
  }

  // Handle dispensing control (Button B to pause/resume)
  if (dispensingStarted && !hasDispensed) {
    int bState = digitalRead(BUTTON_B_PIN);
    int aState = digitalRead(BUTTON_A_PIN);
    unsigned long nowMs = millis();
    
    // Check for pause/resume buttons
    if (bState == HIGH && (nowMs - lastButtonPressMs > 500) && !dispensingPaused) {
      lastButtonPressMs = nowMs;
      // Pause dispensing
      dispensingPaused = true;
      remainingDispensingTime = totalDispensingTime - (nowMs - dispensingStartTime);
      showDispensingPaused();
      Serial.println("[System] Dispensing paused by user");
      
      // Close servo when paused
      const int startAngle = 103;
      const int openAngle = 30;
      bool useB = (selectedRiceName == riceBName);
      for (int pos = openAngle; pos <= startAngle; pos++) {
        if (useB) { servoB.write(pos); } else { servoA.write(pos); }
        delay(10);
      }
      if (useB) { servoB.write(startAngle); } else { servoA.write(startAngle); }
      Serial.println("[System] Servo closed - dispensing paused");
      
    } else if (aState == HIGH && (nowMs - lastButtonPressMs > 500) && dispensingPaused) {
      lastButtonPressMs = nowMs;
      // Resume dispensing
      dispensingPaused = false;
      dispensingStartTime = nowMs - (totalDispensingTime - remainingDispensingTime);
      showDispensingControl();
      Serial.println("[System] Dispensing resumed by user");
      
      // Open servo when resumed
      const int startAngle = 103;
      const int openAngle = 30;
      bool useB = (selectedRiceName == riceBName);
      for (int pos = startAngle; pos >= openAngle; pos--) {
        if (useB) { servoB.write(pos); } else { servoA.write(pos); }
        delay(10);
      }
      Serial.println("[System] Servo opened - dispensing resumed");
    }
    
    // Update dispensing progress
    if (!dispensingPaused && (nowMs - lastDispensingUpdate > 1000)) {
      lastDispensingUpdate = nowMs;
      unsigned long elapsed = nowMs - dispensingStartTime;
      if (elapsed >= totalDispensingTime) {
        // Dispensing complete
        dispensingStarted = false;
        hasDispensed = true;
        
        Serial.println("[System] Dispensing complete! Closing dispenser...");
        // Close dispenser
        const int startAngle = 103;
        const int openAngle = 30;
        bool useB = (selectedRiceName == riceBName);
        for (int pos = openAngle; pos <= startAngle; pos++) {
          if (useB) { servoB.write(pos); } else { servoA.write(pos); }
          delay(10);
        }
        if (useB) { servoB.write(startAngle); } else { servoA.write(startAngle); }
        if (useB) { servoB.detach(); } else { servoA.detach(); }
        
        // Show receipt waiting
        showReceiptWaiting();
        delay(2000);
        
        // Print receipt using stored transaction ID
        printReceipt(currentTransactionId, totalAmount);
        
        // Show thank you message
        showThankYou();
        delay(3000);
        
        // Reset system
        Serial.println("[System] Resetting system...");
        totalAmount = 0;
        coinInserted = false;
        riceSelected = false;
        selectedRiceName = "";
        quantitySelected = false;
        selectedQuantityKg = 1.0;
        transactionConfirmed = false;
        showingStartPrompt = false;
        dispensingStarted = false;
        dispensingPaused = false;
        remainingDispensingTime = 0;
        hasDispensed = false; // Reset dispensing flag
        backButtonShowTime = 0; // Reset back button timer
        currentTransactionId = 0; // Reset transaction ID
        
        // Reset custom purchase variables
        customPurchaseMode = false;
        showingConfirmPrompt = false;
        confirmDisplayToggleTime = 0;
        showConfirmText = true;
        
        // Reset bill detection variables
        billPulseCount = 0;
        billDetectionStartTime = 0;
        lastBillPulseTime = 0;
        
        // Small delay to ensure system is fully reset
        delay(1000);
        
        // Use the dynamic display function instead of hardcoded text
        displaySelectionScreen();
      }
    }
  }

  if (totalAmount < (pricePerKg * (quantitySelected ? selectedQuantityKg : 1))) {
    hasDispensed = false;
  }
}

// Function to update payment display smoothly with coordinated clearing
void updatePaymentDisplay() {
  static unsigned long lastUpdateTime = 0;
  static int lastDisplayedAmount = -1;
  
  // Prevent rapid successive updates (debounce) - increased to 200ms
  if (millis() - lastUpdateTime < 200 && totalAmount == lastDisplayedAmount) {
    return; // Skip update if too soon and amount unchanged
  }
  
  // Only update if we're in the correct state
  if (!riceSelected || !quantitySelected || dispensingStarted || showingStartPrompt) {
    return; // Don't update display if not in payment state
  }
  
  int totalPrice = (int)(pricePerKg * selectedQuantityKg);
  
  // Clear display with small delay for smoother transition
  lcd.clear();
  delay(5); // Minimal delay to prevent jarring effect
  
  lcd.setCursor(0, 0);
  lcd.print("Total: " + String(totalPrice));
  lcd.setCursor(0, 1);
  
  if (totalAmount >= totalPrice) {
    lcd.print("Ready to dispense!");
  } else if (customPurchaseMode && showingConfirmPrompt) {
    // Show alternating display for custom purchase confirmation
    if (millis() - confirmDisplayToggleTime > 1500) { // Toggle every 1.5 seconds
      confirmDisplayToggleTime = millis();
      showConfirmText = !showConfirmText;
    }
    
    if (showConfirmText) {
      lcd.print("Balance: " + String(totalAmount) + " PHP");
    } else {
      lcd.print("A. Confirm");
    }
  } else {
    lcd.print("Balance: " + String(totalAmount) + " PHP");
  }
  
  lastUpdateTime = millis();
  lastDisplayedAmount = totalAmount;
}

void displaySelectionScreen() {
  // Header
  lcd.setCursor(0, 0);
  if (riceAName.length() == 0 && riceBName.length() == 0) {
    lcd.print(padRight16("No rice config"));
  } else if (riceAName.length() == 0 || riceBName.length() == 0) {
    lcd.print(padRight16("Available Rice"));
  } else {
    lcd.print(padRight16("Choose a Rice"));
  }
  
  // Alternate between A and B every 6000 ms (6 seconds for complete scrolling)
  unsigned long nowMs = millis();
  if (nowMs - selectionLastToggleMs > 2000) {
    selectionLastToggleMs = nowMs;
    selectionShowA = !selectionShowA;
  }
  
  String optionLine;
  
  // Handle different scenarios based on available rice
  if (riceAName.length() > 0 && riceBName.length() > 0) {
    // Both rice items available - alternate between them
  if (selectionShowA) {
    // Check if rice A is expired or has low stock
    float stockPercentageA = (riceAStock / riceACapacity) * 100;
      Serial.printf("[Display] Rice A - Expired: %s, Stock: %.1f%%, Name: %s\n", 
                    riceAExpired ? "YES" : "NO", stockPercentageA, riceAName.c_str());
    if (riceAExpired) {
      optionLine = "A: Unavailable";
        Serial.println("[Display] Showing Rice A as Unavailable (expired)");
    } else if (stockPercentageA < 5.0f) {
      optionLine = "A: Out of stock";
        Serial.println("[Display] Showing Rice A as Out of stock");
    } else {
      optionLine = String("A: ") + riceAName + "-" + String(riceAPricePerKg) + "/kg";
        Serial.println("[Display] Showing Rice A as available");
    }
  } else {
    // Check if rice B is expired or has low stock
    float stockPercentageB = (riceBStock / riceBCapacity) * 100;
      Serial.printf("[Display] Rice B - Expired: %s, Stock: %.1f%%, Name: %s\n", 
                    riceBExpired ? "YES" : "NO", stockPercentageB, riceBName.c_str());
    if (riceBExpired) {
      optionLine = "B: Unavailable";
        Serial.println("[Display] Showing Rice B as Unavailable (expired)");
    } else if (stockPercentageB < 5.0f) {
      optionLine = "B: Out of stock";
        Serial.println("[Display] Showing Rice B as Out of stock");
    } else {
      optionLine = String("B: ") + riceBName + "-" + String(riceBPricePerKg) + "/kg";
        Serial.println("[Display] Showing Rice B as available");
      }
    }
  } else if (riceAName.length() > 0) {
    // Only rice A available
    float stockPercentageA = (riceAStock / riceACapacity) * 100;
    Serial.printf("[Display] Only Rice A - Expired: %s, Stock: %.1f%%, Name: %s\n", 
                  riceAExpired ? "YES" : "NO", stockPercentageA, riceAName.c_str());
    if (riceAExpired) {
      optionLine = "A: Unavailable";
      Serial.println("[Display] Showing Rice A as Unavailable (expired)");
    } else if (stockPercentageA < 5.0f) {
      optionLine = "A: Out of stock";
      Serial.println("[Display] Showing Rice A as Out of stock");
    } else {
      optionLine = String("A: ") + riceAName + "-" + String(riceAPricePerKg) + "/kg";
      Serial.println("[Display] Showing Rice A as available");
    }
  } else if (riceBName.length() > 0) {
    // Only rice B available
    float stockPercentageB = (riceBStock / riceBCapacity) * 100;
    Serial.printf("[Display] Only Rice B - Expired: %s, Stock: %.1f%%, Name: %s\n", 
                  riceBExpired ? "YES" : "NO", stockPercentageB, riceBName.c_str());
    if (riceBExpired) {
      optionLine = "B: Unavailable";
      Serial.println("[Display] Showing Rice B as Unavailable (expired)");
    } else if (stockPercentageB < 5.0f) {
      optionLine = "B: Out of stock";
      Serial.println("[Display] Showing Rice B as Out of stock");
    } else {
      optionLine = String("B: ") + riceBName + "-" + String(riceBPricePerKg) + "/kg";
      Serial.println("[Display] Showing Rice B as available");
    }
  } else {
    // No rice available
    optionLine = "No rice available";
    Serial.println("[Display] No rice available");
  }
  
  // Display the option line normally
  lcd.setCursor(0, 1);
  lcd.print(padRight16(optionLine));
}

String padRight16(const String &text) {
  String s = text;
  if ((int)s.length() > 16) return s.substring(0, 16);
  while ((int)s.length() < 16) s += " ";
  return s;
}



// Function to clear rice data
void clearRiceData() {
  riceAName = "";
  riceBName = "";
  riceAPricePerKg = 0;
  riceBPricePerKg = 0;
  riceACapacity = 10.0f;
  riceBCapacity = 10.0f;
  riceAExpiration = "";
  riceBExpiration = "";
  riceAExpired = false;
  riceBExpired = false;
  Serial.println("[Config] Rice data cleared");
}

// Function to force immediate refresh
void forceRefresh() {
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[Config] Force refresh requested");
    lastConfigFetchMs = 0; // Reset timer to force immediate refresh
    fetchRiceConfig();
  }
}

void fetchRiceConfig() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[Config] WiFi not connected, cannot fetch rice configuration");
    return;
  }
  
  Serial.println("[Config] Attempting to fetch rice configuration...");
  Serial.printf("[Config] URL: %s\n", configUrl);
  Serial.printf("[Config] Local IP: %s\n", WiFi.localIP().toString().c_str());
  
  // Clear existing data first
  clearRiceData();
  
  HTTPClient http;
  http.begin(configUrl);
  http.setTimeout(10000); // 10 second timeout
  
  int code = http.GET();
  Serial.printf("[Config] HTTP Response code: %d\n", code);
  
  if (code > 0) {
    String payload = http.getString();
    Serial.println("[Config] Response: " + payload);
    
    // Check if response is valid JSON
    if (payload.indexOf("{") == -1 || payload.indexOf("}") == -1) {
      Serial.println("[Config] Invalid JSON response, using defaults");
      return;
    }
    
    // Parse JSON response with proper structure: {"success":true,"data":{"items":[...]}}
    // Find the items array
    int itemsStart = payload.indexOf("\"items\":[");
    if (itemsStart == -1) {
      Serial.println("[Config] No items array found in response");
      return;
    }
    
    // Find the first item object (look for the first { after "items":[)
    int item1Start = payload.indexOf("{", itemsStart);
    if (item1Start == -1) {
      Serial.println("[Config] No first item found - inventory is empty");
      // This is OK - inventory might be empty
      return;
    }
    
    // Find the matching closing brace for the first item
    int braceCount = 0;
    int item1End = item1Start;
    for (int i = item1Start; i < (int)payload.length(); i++) {
      if (payload[i] == '{') braceCount++;
      if (payload[i] == '}') braceCount--;
      if (braceCount == 0) {
        item1End = i;
        break;
      }
    }
    
    if (item1End == item1Start) {
      Serial.println("[Config] No first item closing brace found");
      return;
    }
    
    // Parse first item
    String item1 = payload.substring(item1Start, item1End + 1);
    Serial.printf("[Config] First item: %s\n", item1.c_str());
    
    // Extract first item data
    int n1s = item1.indexOf("\"name\":\"");
    if (n1s != -1) {
      n1s += 8;
      int n1e = item1.indexOf("\"", n1s);
      if (n1e != -1) {
        riceAName = item1.substring(n1s, n1e);
        Serial.printf("[Config] Found rice A: %s\n", riceAName.c_str());
      }
    }
    
    int p1s = item1.indexOf("\"price\":");
    if (p1s != -1) {
      p1s += 8;
      int p1e = p1s;
      while (p1e < (int)item1.length() && (isDigit(item1[p1e]) || item1[p1e] == '.' )) p1e++;
      riceAPricePerKg = (int)item1.substring(p1s, p1e).toFloat();
      Serial.printf("[Config] Found price A: %d\n", riceAPricePerKg);
    }
    
    int c1s = item1.indexOf("\"capacity\":");
    if (c1s != -1) {
      c1s += 11;
      int c1e = c1s;
      while (c1e < (int)item1.length() && (isDigit(item1[c1e]) || item1[c1e] == '.' )) c1e++;
      riceACapacity = item1.substring(c1s, c1e).toFloat();
      Serial.printf("[Config] Found capacity A: %.1f\n", riceACapacity);
    }
    
    int e1s = item1.indexOf("\"expiration_date\":\"");
    if (e1s != -1) {
      e1s += 19;
      int e1e = item1.indexOf("\"", e1s);
      if (e1e != -1) {
        riceAExpiration = item1.substring(e1s, e1e);
        Serial.printf("[Config] Raw expiration A: '%s'\n", riceAExpiration.c_str());
        riceAExpired = isRiceExpired(riceAExpiration);
        Serial.printf("[Config] Found expiration A: %s (Expired: %s)\n", riceAExpiration.c_str(), riceAExpired ? "Yes" : "No");
      } else {
        Serial.println("[Config] No closing quote found for expiration A");
      }
    } else {
      Serial.println("[Config] No expiration_date field found for rice A");
    }
    
    // Find the second item object (look for the next { after the first item)
    int item2Start = payload.indexOf("{", item1End + 1);
    if (item2Start == -1) {
      Serial.println("[Config] No second item found - only one rice item available");
      // This is OK - we can continue with just one rice item
      // Don't return, just skip the second item parsing
    } else {
    
    // Find the matching closing brace for the second item
    int item2End = item2Start;
    for (int i = item2Start; i < (int)payload.length(); i++) {
      if (payload[i] == '{') braceCount++;
      if (payload[i] == '}') braceCount--;
      if (braceCount == 0) {
        item2End = i;
        break;
      }
    }
    
    if (item2End == item2Start) {
      Serial.println("[Config] No second item closing brace found");
      return;
    }
    
    // Parse second item
    String item2 = payload.substring(item2Start, item2End + 1);
    Serial.printf("[Config] Second item: %s\n", item2.c_str());
    
    // Extract second item data
    int n2s = item2.indexOf("\"name\":\"");
    if (n2s != -1) {
      n2s += 8;
      int n2e = item2.indexOf("\"", n2s);
      if (n2e != -1) {
        riceBName = item2.substring(n2s, n2e);
        Serial.printf("[Config] Found rice B: %s\n", riceBName.c_str());
      }
    }
    
    int p2s = item2.indexOf("\"price\":");
    if (p2s != -1) {
      p2s += 8;
      int p2e = p2s;
      while (p2e < (int)item2.length() && (isDigit(item2[p2e]) || item2[p2e] == '.' )) p2e++;
      riceBPricePerKg = (int)item2.substring(p2s, p2e).toFloat();
      Serial.printf("[Config] Found price B: %d\n", riceBPricePerKg);
    }
    
    int c2s = item2.indexOf("\"capacity\":");
    if (c2s != -1) {
      c2s += 11;
      int c2e = c2s;
      while (c2e < (int)item2.length() && (isDigit(item2[c2e]) || item2[c2e] == '.' )) c2e++;
      riceBCapacity = item2.substring(c2s, c2e).toFloat();
      Serial.printf("[Config] Found capacity B: %.1f\n", riceBCapacity);
    }
    
    int e2s = item2.indexOf("\"expiration_date\":\"");
    if (e2s != -1) {
      e2s += 19;
      int e2e = item2.indexOf("\"", e2s);
      if (e2e != -1) {
        riceBExpiration = item2.substring(e2s, e2e);
          Serial.printf("[Config] Raw expiration B: '%s'\n", riceBExpiration.c_str());
        riceBExpired = isRiceExpired(riceBExpiration);
        Serial.printf("[Config] Found expiration B: %s (Expired: %s)\n", riceBExpiration.c_str(), riceBExpired ? "Yes" : "No");
        } else {
          Serial.println("[Config] No closing quote found for expiration B");
        }
      } else {
        Serial.println("[Config] No expiration_date field found for rice B");
      }
    }
    
    // Verify we got valid data for at least one rice
    if (riceAName.length() > 0 && riceAPricePerKg > 0 && riceACapacity > 0) {
      if (riceBName.length() > 0 && riceBPricePerKg > 0 && riceBCapacity > 0) {
        // Both rice items available
      Serial.printf("[Config] Success! A: %s @ %d (%.1fkg), B: %s @ %d (%.1fkg)\n", 
                    riceAName.c_str(), riceAPricePerKg, riceACapacity, 
                    riceBName.c_str(), riceBPricePerKg, riceBCapacity);
    } else {
        // Only rice A available
        Serial.printf("[Config] Success! A: %s @ %d (%.1fkg), B: Not available\n", 
                      riceAName.c_str(), riceAPricePerKg, riceACapacity);
      }
    } else {
      Serial.println("[Config] No valid rice data received");
    }
  } else {
    Serial.printf("[Config] GET failed: %s\n", http.errorToString(code).c_str());
    Serial.println("[Config] Failed to fetch rice configuration from server");
  }
  http.end();
}
