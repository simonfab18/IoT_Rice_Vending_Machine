<?php
// Script to update Jasmine rice expiration to today
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $today = date('Y-m-d');
    
    // Update Jasmine rice expiration to today
    $stmt = $conn->prepare("UPDATE rice_inventory SET expiration_date = ? WHERE name LIKE '%Jasmine%'");
    $result = $stmt->execute([$today]);
    
    if ($result) {
        echo "<h2>Jasmine Rice Expiration Updated</h2>\n";
        echo "<p style='color: green;'>Jasmine rice expiration date has been set to today: <strong>$today</strong></p>\n";
        
        // Show current inventory status
        $stmt = $conn->query("SELECT name, price, expiration_date FROM rice_inventory ORDER BY id");
        $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Inventory Status:</h3>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Name</th><th>Price</th><th>Expiration Date</th><th>Status</th></tr>\n";
        
        foreach ($riceInventory as $rice) {
            $expDate = new DateTime($rice['expiration_date']);
            $todayDate = new DateTime();
            
            if ($expDate < $todayDate) {
                $status = "<span style='color: red;'>EXPIRED</span>";
            } elseif ($expDate->format('Y-m-d') == $todayDate->format('Y-m-d')) {
                $status = "<span style='color: orange; font-weight: bold;'>EXPIRES TODAY</span>";
            } else {
                $daysLeft = $todayDate->diff($expDate)->days;
                $status = "<span style='color: green;'>Good for $daysLeft days</span>";
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rice['name']) . "</td>";
            echo "<td>₱" . number_format($rice['price'], 2) . "</td>";
            echo "<td>" . $rice['expiration_date'] . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<h3>Expected Arduino Behavior:</h3>\n";
        echo "<ul>\n";
        echo "<li>Jasmine rice should now show as <strong>'A: Unavailable'</strong> (expires today)</li>\n";
        echo "<li>Dinorado rice should show as <strong>'B: Dinorado-60/kg'</strong> (not expired)</li>\n";
        echo "<li>LCD should alternate between these two options</li>\n";
        echo "</ul>\n";
        
        echo "<h3>Next Steps:</h3>\n";
        echo "<ol>\n";
        echo "<li>Upload the updated Arduino code</li>\n";
        echo "<li>Press any button on the Arduino to force refresh</li>\n";
        echo "<li>Check Serial Monitor for expiration debug messages</li>\n";
        echo "<li>Verify LCD shows 'A: Unavailable' for Jasmine rice</li>\n";
        echo "</ol>\n";
        
    } else {
        echo "<p style='color: red;'>Failed to update Jasmine rice expiration date</p>\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
