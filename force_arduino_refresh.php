<?php
// Script to force Arduino refresh by calling the API endpoint
echo "<h2>Forcing Arduino Configuration Refresh</h2>\n";

// Get the Arduino's IP address (you may need to adjust this)
$arduinoIP = "192.168.1.100"; // Change this to your Arduino's IP
$apiUrl = "http://$arduinoIP/get_rice_config.php";

echo "<p>Attempting to call Arduino API at: <code>$apiUrl</code></p>\n";

// Make a request to the Arduino's API endpoint
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>Failed to connect to Arduino. Make sure:</p>\n";
    echo "<ul>\n";
    echo "<li>Arduino is connected to WiFi</li>\n";
    echo "<li>Arduino IP address is correct: <code>$arduinoIP</code></li>\n";
    echo "<li>Arduino is running the web server</li>\n";
    echo "</ul>\n";
} else {
    echo "<p style='color: green;'>Successfully called Arduino API!</p>\n";
    echo "<h3>Response:</h3>\n";
    echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
}

echo "<h3>Alternative Method - Manual Refresh:</h3>\n";
echo "<ol>\n";
echo "<li>Press any button (A or B) on the Arduino</li>\n";
echo "<li>This will trigger <code>forceRefresh()</code> function</li>\n";
echo "<li>Check Serial Monitor for debug messages</li>\n";
echo "<li>Look for the expiration checking messages</li>\n";
echo "</ol>\n";

echo "<h3>Expected Serial Monitor Output:</h3>\n";
echo "<pre>\n";
echo "[Config] Button A pressed - forcing config refresh\n";
echo "[Config] Force refresh requested\n";
echo "[Config] Attempting to fetch rice configuration...\n";
echo "[Config] Raw expiration A: '2025-09-21'\n";
echo "[Expiration] Checking expiration date: 2025-09-21\n";
echo "[Expiration] Parsed expiration: 2025-09-21\n";
echo "[Expiration] Current date: 2025-09-21\n";
echo "[Expiration] Expired: day is today or in the past\n";
echo "[Config] Found expiration A: 2025-09-21 (Expired: Yes)\n";
echo "[Display] Rice A - Expired: YES, Stock: XX.X%, Name: Jasmine\n";
echo "[Display] Showing Rice A as Unavailable (expired)\n";
echo "</pre>\n";
?>
