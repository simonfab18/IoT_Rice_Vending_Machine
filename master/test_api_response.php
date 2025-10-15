<?php
// Test script to check what the Arduino receives from the API
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get the same data that the Arduino would receive
    $stmt = $conn->query("SELECT id, name, price, unit, capacity, expiration_date FROM rice_inventory ORDER BY id");
    $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>API Response Test</h2>\n";
    echo "<p>This shows exactly what the Arduino receives from get_rice_config.php</p>\n";
    
    // Simulate the API response
    $response = [
        'success' => true,
        'data' => [
            'items' => $riceInventory
        ]
    ];
    
    $jsonResponse = json_encode($response, JSON_PRETTY_PRINT);
    
    echo "<h3>JSON Response:</h3>\n";
    echo "<pre>" . htmlspecialchars($jsonResponse) . "</pre>\n";
    
    echo "<h3>Current Date:</h3>\n";
    echo "<p>Today is: <strong>" . date('Y-m-d') . "</strong></p>\n";
    
    echo "<h3>Expiration Analysis:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Name</th><th>Expiration Date</th><th>Should be Expired?</th><th>Reason</th></tr>\n";
    
    foreach ($riceInventory as $rice) {
        $expDate = $rice['expiration_date'];
        $today = date('Y-m-d');
        
        $shouldBeExpired = false;
        $reason = "";
        
        if ($expDate <= $today) {
            $shouldBeExpired = true;
            $reason = "Expiration date ($expDate) is today or in the past";
        } else {
            $reason = "Expiration date ($expDate) is in the future";
        }
        
        $color = $shouldBeExpired ? "red" : "green";
        echo "<tr>";
        echo "<td>" . htmlspecialchars($rice['name']) . "</td>";
        echo "<td>" . $expDate . "</td>";
        echo "<td style='color: $color; font-weight: bold;'>" . ($shouldBeExpired ? "YES" : "NO") . "</td>";
        echo "<td>" . $reason . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>Expected Arduino Behavior:</h3>\n";
    echo "<ul>\n";
    foreach ($riceInventory as $index => $rice) {
        $expDate = $rice['expiration_date'];
        $today = date('Y-m-d');
        $letter = $index == 0 ? 'A' : 'B';
        
        if ($expDate <= $today) {
            echo "<li><strong>$letter: Unavailable</strong> - " . $rice['name'] . " (expired on $expDate)</li>\n";
        } else {
            echo "<li><strong>$letter: " . $rice['name'] . "-" . $rice['price'] . "/kg</strong> - Available</li>\n";
        }
    }
    echo "</ul>\n";
    
    echo "<h3>Debug Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Upload the updated Arduino code</li>\n";
    echo "<li>Open Serial Monitor (115200 baud)</li>\n";
    echo "<li>Press any button on the Arduino to force refresh</li>\n";
    echo "<li>Look for these debug messages:</li>\n";
    echo "<ul>\n";
    echo "<li><code>[Config] Raw expiration A: '2025-09-21'</code></li>\n";
    echo "<li><code>[Expiration] Checking expiration date: 2025-09-21</code></li>\n";
    echo "<li><code>[Expiration] Current date: 2025-09-21</code></li>\n";
    echo "<li><code>[Expiration] Expired: day is today or in the past</code></li>\n";
    echo "<li><code>[Config] Found expiration A: 2025-09-21 (Expired: Yes)</code></li>\n";
    echo "<li><code>[Display] Rice A - Expired: YES, Stock: XX.X%, Name: Jasmine</code></li>\n";
    echo "<li><code>[Display] Showing Rice A as Unavailable (expired)</code></li>\n";
    echo "</ul>\n";
    echo "</ol>\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
