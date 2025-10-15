<?php
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get current inventory data
    $stmt = $conn->query("SELECT id, name, price, expiration_date FROM rice_inventory ORDER BY id");
    $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Current Rice Inventory with Expiration Dates</h2>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Expiration Date</th><th>Status</th></tr>\n";
    
    $today = new DateTime();
    
    foreach ($riceInventory as $rice) {
        $expirationDate = null;
        $status = "No expiration date";
        
        if ($rice['expiration_date']) {
            $expirationDate = new DateTime($rice['expiration_date']);
            $daysLeft = $today->diff($expirationDate)->days;
            
            if ($expirationDate < $today) {
                $status = "<span style='color: red; font-weight: bold;'>EXPIRED</span>";
            } elseif ($daysLeft <= 7) {
                $status = "<span style='color: orange; font-weight: bold;'>Expires in {$daysLeft} days</span>";
            } elseif ($daysLeft <= 30) {
                $status = "<span style='color: yellow;'>Expires in {$daysLeft} days</span>";
            } else {
                $status = "<span style='color: green;'>Good for {$daysLeft} days</span>";
            }
        }
        
        echo "<tr>";
        echo "<td>" . $rice['id'] . "</td>";
        echo "<td>" . htmlspecialchars($rice['name']) . "</td>";
        echo "<td>₱" . number_format($rice['price'], 2) . "</td>";
        echo "<td>" . ($rice['expiration_date'] ?: 'Not set') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<h3>Current Date: " . $today->format('Y-m-d H:i:s') . "</h3>\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
