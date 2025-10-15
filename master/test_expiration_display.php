<?php
// Test script to verify expiration display logic
require_once 'database.php';

echo "<h2>Testing Rice Expiration Display</h2>\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get current inventory data
    $stmt = $conn->query("SELECT id, name, price, expiration_date FROM rice_inventory ORDER BY id");
    $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Inventory Status:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Expiration Date</th><th>Status</th><th>Expected LCD Display</th></tr>\n";
    
    $today = new DateTime();
    $riceIndex = 0;
    
    foreach ($riceInventory as $rice) {
        $riceIndex++;
        $expirationDate = null;
        $status = "No expiration date";
        $lcdDisplay = "";
        
        if ($rice['expiration_date']) {
            $expirationDate = new DateTime($rice['expiration_date']);
            $daysLeft = $today->diff($expirationDate)->days;
            
            if ($expirationDate < $today) {
                $status = "<span style='color: red; font-weight: bold;'>EXPIRED</span>";
                $lcdDisplay = ($riceIndex == 1 ? "A: " : "B: ") . "Unavailable";
            } elseif ($daysLeft <= 7) {
                $status = "<span style='color: orange; font-weight: bold;'>Expires in {$daysLeft} days</span>";
                $lcdDisplay = ($riceIndex == 1 ? "A: " : "B: ") . htmlspecialchars($rice['name']) . "-" . number_format($rice['price']) . "/kg";
            } elseif ($daysLeft <= 30) {
                $status = "<span style='color: yellow;'>Expires in {$daysLeft} days</span>";
                $lcdDisplay = ($riceIndex == 1 ? "A: " : "B: ") . htmlspecialchars($rice['name']) . "-" . number_format($rice['price']) . "/kg";
            } else {
                $status = "<span style='color: green;'>Good for {$daysLeft} days</span>";
                $lcdDisplay = ($riceIndex == 1 ? "A: " : "B: ") . htmlspecialchars($rice['name']) . "-" . number_format($rice['price']) . "/kg";
            }
        } else {
            $lcdDisplay = ($riceIndex == 1 ? "A: " : "B: ") . htmlspecialchars($rice['name']) . "-" . number_format($rice['price']) . "/kg";
        }
        
        echo "<tr>";
        echo "<td>" . $rice['id'] . "</td>";
        echo "<td>" . htmlspecialchars($rice['name']) . "</td>";
        echo "<td>₱" . number_format($rice['price'], 2) . "</td>";
        echo "<td>" . ($rice['expiration_date'] ?: 'Not set') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "<td style='font-family: monospace; font-weight: bold;'>" . $lcdDisplay . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<h3>Current Date: " . $today->format('Y-m-d H:i:s') . "</h3>\n";
    
    echo "<h3>API Response Test:</h3>\n";
    $url = "http://localhost/rice_dispenser_iot-master/get_rice_config.php";
    $response = file_get_contents($url);
    echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
    
    echo "<h3>Expected Arduino Behavior:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>If Jasmine Rice is expired:</strong> LCD should show 'A: Unavailable' and 'B: Dinorado-60/kg'</li>\n";
    echo "<li><strong>If both rice are available:</strong> LCD should alternate between 'A: Dinorado-60/kg' and 'B: Jasmine-65/kg'</li>\n";
    echo "<li><strong>If only one rice available:</strong> LCD should show 'Available Rice' and the rice name</li>\n";
    echo "</ul>\n";
    
    echo "<h3>Troubleshooting Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Check Arduino Serial Monitor for expiration checking messages</li>\n";
    echo "<li>Look for '[Expiration]' debug messages showing date comparison</li>\n";
    echo "<li>Look for '[Display]' messages showing what's being displayed</li>\n";
    echo "<li>Press any button to force immediate refresh</li>\n";
    echo "</ol>\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
