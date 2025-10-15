<?php
// Test script to verify Hostinger database connection
require_once 'database.php';

echo "<h2>Hostinger Database Connection Test</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test database name
    $stmt = $conn->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Connected to database:</strong> " . $result['db_name'] . "</p>";
    
    // Test if tables exist
    $tables = ['transactions', 'rice_inventory', 'machine_heartbeat', 'alerts', 'reports'];
    
    echo "<h3>Table Status:</h3>";
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Table '$table' exists (records: {$result['count']})</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Table '$table' does not exist or has errors</p>";
        }
    }
    
    // Test rice inventory data
    echo "<h3>Rice Inventory Data:</h3>";
    try {
        $stmt = $conn->query("SELECT * FROM rice_inventory");
        $riceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($riceData)) {
            echo "<p style='color: orange;'>⚠️ No rice data found. You may need to add rice items.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Capacity</th><th>Expiration</th></tr>";
            foreach ($riceData as $rice) {
                echo "<tr>";
                echo "<td>{$rice['id']}</td>";
                echo "<td>{$rice['name']}</td>";
                echo "<td>₱{$rice['price']}</td>";
                echo "<td>{$rice['stock']} {$rice['unit']}</td>";
                echo "<td>{$rice['capacity']} {$rice['unit']}</td>";
                echo "<td>{$rice['expiration_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error reading rice inventory: " . $e->getMessage() . "</p>";
    }
    
    // Test transactions
    echo "<h3>Recent Transactions:</h3>";
    try {
        $stmt = $conn->query("SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT 5");
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($transactions)) {
            echo "<p style='color: orange;'>⚠️ No transactions found yet.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Amount</th><th>Rice</th><th>Quantity</th><th>Date</th></tr>";
            foreach ($transactions as $transaction) {
                echo "<tr>";
                echo "<td>{$transaction['id']}</td>";
                echo "<td>₱{$transaction['total_amount']}</td>";
                echo "<td>{$transaction['rice_name']}</td>";
                echo "<td>{$transaction['quantity']} kg</td>";
                echo "<td>{$transaction['transaction_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error reading transactions: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common issues:</strong></p>";
    echo "<ul>";
    echo "<li>Check database credentials in database.php</li>";
    echo "<li>Verify database exists in Hostinger cPanel</li>";
    echo "<li>Check if database user has proper permissions</li>";
    echo "<li>Ensure database server is running</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If connection is successful, upload all PHP files to Hostinger</li>";
echo "<li>Create database tables using the SQL commands in HOSTINGER_SETUP.md</li>";
echo "<li>Update Arduino code with your WiFi credentials</li>";
echo "<li>Test the complete system</li>";
echo "</ol>";
?>
