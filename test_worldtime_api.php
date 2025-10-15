<?php
// Test script to check worldtimeapi.org response
echo "<h2>WorldTime API Test</h2>\n";

$apiUrl = "http://worldtimeapi.org/api/timezone/Asia/Manila";

echo "<p>Testing API: <code>$apiUrl</code></p>\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>Failed to connect to WorldTime API</p>\n";
} else {
    echo "<p style='color: green;'>Successfully connected to WorldTime API!</p>\n";
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['datetime'])) {
        $datetime = $data['datetime'];
        $currentDate = substr($datetime, 0, 10); // Extract YYYY-MM-DD
        
        echo "<h3>API Response:</h3>\n";
        echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
        
        echo "<h3>Parsed Information:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Full DateTime:</strong> $datetime</li>\n";
        echo "<li><strong>Current Date:</strong> $currentDate</li>\n";
        echo "<li><strong>Timezone:</strong> " . ($data['timezone'] ?? 'Unknown') . "</li>\n";
        echo "</ul>\n";
        
        echo "<h3>Expiration Check Simulation:</h3>\n";
        $jasmineExpiration = "2025-09-21";
        
        echo "<p>Jasmine rice expiration: <strong>$jasmineExpiration</strong></p>\n";
        echo "<p>Current date from API: <strong>$currentDate</strong></p>\n";
        
        if ($jasmineExpiration <= $currentDate) {
            echo "<p style='color: red; font-weight: bold;'>✓ Jasmine rice SHOULD be expired (expiration date is today or in the past)</p>\n";
        } else {
            echo "<p style='color: green; font-weight: bold;'>✗ Jasmine rice should NOT be expired (expiration date is in the future)</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>Invalid response format from WorldTime API</p>\n";
        echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
    }
}

echo "<h3>Possible Issues:</h3>\n";
echo "<ul>\n";
echo "<li><strong>WiFi Connection:</strong> Arduino might not be connected to WiFi</li>\n";
echo "<li><strong>API Timeout:</strong> WorldTime API might be taking too long to respond</li>\n";
echo "<li><strong>Date Parsing:</strong> Arduino might not be parsing the date correctly</li>\n";
echo "<li><strong>Configuration Cache:</strong> Arduino might be using cached configuration</li>\n";
echo "</ul>\n";

echo "<h3>Debug Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Check Arduino Serial Monitor for WiFi connection status</li>\n";
echo "<li>Look for <code>[Expiration]</code> debug messages</li>\n";
echo "<li>Verify the current date being fetched from API</li>\n";
echo "<li>Check if expiration comparison logic is working</li>\n";
echo "</ol>\n";
?>
