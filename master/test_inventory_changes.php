<?php
// Test script to simulate inventory changes and see API response
require_once 'database.php';

echo "<h2>Testing Inventory Changes and API Response</h2>\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h3>Current Inventory:</h3>\n";
    $stmt = $conn->query("SELECT id, name, price, expiration_date FROM rice_inventory ORDER BY id");
    $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($riceInventory)) {
        echo "<p style='color: red;'>No rice items in inventory</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Expiration</th><th>Actions</th></tr>\n";
        foreach ($riceInventory as $rice) {
            echo "<tr>";
            echo "<td>" . $rice['id'] . "</td>";
            echo "<td>" . htmlspecialchars($rice['name']) . "</td>";
            echo "<td>₱" . number_format($rice['price'], 2) . "</td>";
            echo "<td>" . ($rice['expiration_date'] ?: 'Not set') . "</td>";
            echo "<td><a href='?delete=" . $rice['id'] . "' style='color: red;'>Delete</a></td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Handle delete action
    if (isset($_GET['delete'])) {
        $deleteId = (int)$_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM rice_inventory WHERE id = ?");
        $stmt->execute([$deleteId]);
        echo "<p style='color: green;'>Rice item deleted! Refreshing page...</p>\n";
        echo "<script>setTimeout(function(){ window.location.href = 'test_inventory_changes.php'; }, 2000);</script>\n";
    }
    
    echo "<h3>API Response (get_rice_config.php):</h3>\n";
    $url = "http://localhost/rice_dispenser_iot-master/get_rice_config.php";
    $response = file_get_contents($url);
    echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
    
    $data = json_decode($response, true);
    if ($data && isset($data['data']['items'])) {
        echo "<h3>Parsed Items:</h3>\n";
        echo "<ul>\n";
        foreach ($data['data']['items'] as $index => $item) {
            echo "<li>Item " . ($index + 1) . ": " . htmlspecialchars($item['name']) . " - ₱" . number_format($item['price'], 2) . "</li>\n";
        }
        echo "</ul>\n";
        
        if (empty($data['data']['items'])) {
            echo "<p style='color: orange;'>No items returned - inventory is empty</p>\n";
        }
    }
    
    echo "<h3>Instructions for Testing:</h3>\n";
    echo "<ol>\n";
    echo "<li>Click 'Delete' on any rice item above</li>\n";
    echo "<li>Watch the API response change</li>\n";
    echo "<li>Check your Arduino Serial Monitor - it should refresh within 3 seconds</li>\n";
    echo "<li>Press any button on the Arduino to force immediate refresh</li>\n";
    echo "</ol>\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
