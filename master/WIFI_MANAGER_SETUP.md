# WiFi Manager Setup for Rice Dispenser

## 📚 Library Installation

### Step 1: Install WiFi Manager Library
1. **Open Arduino IDE**
2. **Go to Tools → Manage Libraries**
3. **Search for "WiFiManager"**
4. **Install "WiFiManager by tzapu"** (version 2.0.16 or later)

### Step 2: Alternative Installation (if library manager fails)
1. **Download WiFiManager library** from: https://github.com/tzapu/WiFiManager
2. **Extract the ZIP file**
3. **Copy the WiFiManager folder** to your Arduino libraries folder:
   - Windows: `Documents\Arduino\libraries\`
   - Mac: `~/Documents/Arduino/libraries/`
   - Linux: `~/Arduino/libraries/`

## 🔧 How It Works

### Automatic WiFi Connection:
1. **First Priority**: Tries pre-configured networks:
   - `egg` (password: rolex123)
   - `PLDTHOMEFIBR6d320` (password: hULYO81972-FAB)

2. **Fallback**: If pre-configured networks fail:
   - Creates WiFi hotspot: `RiceDispenser`
   - Password: `rice123`
   - Opens web interface for WiFi setup

### WiFi Setup Process:
1. **Connect to "RiceDispenser"** WiFi network
2. **Open browser** to `192.168.4.1`
3. **Select your WiFi network** from the list
4. **Enter password** and connect
5. **Device saves credentials** and connects automatically

## 📱 Usage Instructions

### For Initial Setup:
1. **Upload the updated Arduino code**
2. **If WiFi fails**, device will create "RiceDispenser" hotspot
3. **Connect your phone/computer** to "RiceDispenser" WiFi
4. **Open browser** to `192.168.4.1`
5. **Select your WiFi network** and enter password
6. **Device will connect** and save credentials

### For Future Use:
- **Device will automatically connect** to saved networks
- **No manual setup needed** unless you change WiFi
- **Automatic reconnection** if connection is lost

## 🔧 Configuration

### Pre-configured Networks (in code):
```cpp
const char* wifi_ssid_1 = "egg";
const char* wifi_password_1 = "rolex123";
const char* wifi_ssid_2 = "PLDTHOMEFIBR6d320";
const char* wifi_password_2 = "hULYO81972-FAB";
```

### WiFi Manager Settings:
- **Hotspot SSID**: `RiceDispenser`
- **Hotspot Password**: `rice123`
- **Timeout**: 5 minutes
- **Auto-reconnect**: Every 30 seconds

## 🚨 Troubleshooting

### If WiFi Manager doesn't work:
1. **Check library installation**
2. **Restart Arduino IDE**
3. **Check ESP32 board selection**
4. **Verify WiFi credentials** in code

### If device doesn't create hotspot:
1. **Check Serial Monitor** for error messages
2. **Reset device** (power cycle)
3. **Check WiFi Manager library** version

### If can't access setup page:
1. **Check device IP** in Serial Monitor
2. **Try different browser**
3. **Clear browser cache**
4. **Check firewall settings**

## 📋 Benefits

✅ **Automatic connection** to known networks
✅ **Easy setup** for new networks
✅ **No code changes** needed for WiFi updates
✅ **Automatic reconnection** if connection lost
✅ **User-friendly** web interface
✅ **Multiple network support**

## 🔄 Reset WiFi Settings

To reset WiFi settings and force setup mode:
1. **Hold reset button** for 10 seconds
2. **Or add this code** to reset WiFi:
```cpp
wm.resetSettings();
ESP.restart();
```

## 📞 Support

If you encounter issues:
1. **Check Serial Monitor** for detailed logs
2. **Verify WiFi credentials** are correct
3. **Test with simple WiFi.begin()** first
4. **Check library compatibility** with ESP32
