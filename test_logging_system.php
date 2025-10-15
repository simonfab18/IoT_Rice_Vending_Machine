<?php
// Test script for Arduino logging system
// This script simulates Arduino sending logs to test the system

require_once 'database.php';

echo "<h2>Arduino Logging System Test</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Test 1: Check if table exists
    echo "<h3>Test 1: Database Table Check</h3>";
    $stmt = $conn->query("SHOW TABLES LIKE 'arduino_logs'");
    if ($stmt->rowCount() > 0) {
        echo "✅ arduino_logs table exists<br>";
    } else {
        echo "❌ arduino_logs table does not exist<br>";
        echo "Please run the create_arduino_logs_table.sql script first.<br>";
        exit;
    }
    
    // Test 2: Insert test logs
    echo "<h3>Test 2: Inserting Test Logs</h3>";
    $testLogs = [
        ['SYSTEM', 'Arduino system started successfully', 'System'],
        ['INFO', 'WiFi connected to network', 'WiFi'],
        ['INFO', 'Rice configuration loaded from server', 'Config'],
        ['DEBUG', 'Button A pressed - rice selection triggered', 'Button'],
        ['INFO', 'Transaction completed successfully', 'Transaction'],
        ['WARNING', 'Low stock detected for Rice A', 'Inventory'],
        ['ERROR', 'Failed to connect to WiFi', 'WiFi'],
        ['INFO', 'Dispensing process started', 'System'],
        ['INFO', 'Receipt printed successfully', 'Printer']
    ];
    
    $stmt = $conn->prepare("INSERT INTO arduino_logs (machine_id, log_level, log_message, log_category) VALUES (?, ?, ?, ?)");
    
    foreach ($testLogs as $log) {
        $stmt->execute(['rice_dispenser_1', $log[0], $log[1], $log[2]]);
        echo "✅ Inserted: {$log[0]} - {$log[1]} ({$log[2]})<br>";
    }
    
    // Test 3: Test API endpoint
    echo "<h3>Test 3: API Endpoint Test</h3>";
    
    // Test GET request
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/arduino_logs_api.php';
    echo "Testing API endpoint: $url<br>";
    
    // Simulate Arduino POST request
    $logData = [
        'machine_id' => 'rice_dispenser_1',
        'log_level' => 'INFO',
        'log_message' => 'Test log from PHP script',
        'log_category' => 'Test'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($logData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ API POST request successful<br>";
        echo "Response: $response<br>";
    } else {
        echo "❌ API POST request failed (HTTP $httpCode)<br>";
        echo "Response: $response<br>";
    }
    
    // Test GET request
    $getUrl = $url . '?limit=5';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $getUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ API GET request successful<br>";
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            echo "✅ Retrieved " . count($data['data']['logs']) . " logs<br>";
        } else {
            echo "❌ Invalid response format<br>";
        }
    } else {
        echo "❌ API GET request failed (HTTP $httpCode)<br>";
    }
    
    // Test 4: Display recent logs
    echo "<h3>Test 4: Recent Logs</h3>";
    $stmt = $conn->query("
        SELECT log_level, log_message, log_category, timestamp 
        FROM arduino_logs 
        ORDER BY timestamp DESC 
        LIMIT 10
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Level</th><th>Message</th><th>Category</th><th>Timestamp</th></tr>";
    
    foreach ($logs as $log) {
        $levelColor = '';
        switch ($log['log_level']) {
            case 'ERROR': $levelColor = 'color: red;'; break;
            case 'WARNING': $levelColor = 'color: orange;'; break;
            case 'INFO': $levelColor = 'color: green;'; break;
            case 'DEBUG': $levelColor = 'color: gray;'; break;
            case 'SYSTEM': $levelColor = 'color: blue;'; break;
        }
        
        echo "<tr>";
        echo "<td style='$levelColor font-weight: bold;'>{$log['log_level']}</td>";
        echo "<td>{$log['log_message']}</td>";
        echo "<td>{$log['log_category']}</td>";
        echo "<td>{$log['timestamp']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 5: Log statistics
    echo "<h3>Test 5: Log Statistics</h3>";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM arduino_logs");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT log_level, COUNT(*) as count FROM arduino_logs GROUP BY log_level");
    $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total logs: $total<br>";
    echo "By level:<br>";
    foreach ($levels as $level) {
        echo "- {$level['log_level']}: {$level['count']}<br>";
    }
    
    echo "<h3>✅ All tests completed!</h3>";
    echo "<p><a href='logs.php'>View the Serial Monitor</a></p>";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
