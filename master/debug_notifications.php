<?php
session_start();
require_once 'database.php';

echo "<h2>Debug Notification System</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if session has visit times
    echo "<h3>Session Data:</h3>";
    echo "<p>Last Transaction Visit: " . (isset($_SESSION['last_transaction_visit']) ? $_SESSION['last_transaction_visit'] : 'Not set') . "</p>";
    echo "<p>Last Alert Visit: " . (isset($_SESSION['last_alert_visit']) ? $_SESSION['last_alert_visit'] : 'Not set') . "</p>";
    
    // Get all alerts
    echo "<h3>All Alerts in Database:</h3>";
    $stmt = $conn->query("SELECT * FROM alerts ORDER BY created_at DESC");
    $allAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($allAlerts)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Type</th><th>Message</th><th>Status</th><th>Created</th></tr>";
        foreach ($allAlerts as $alert) {
            echo "<tr>";
            echo "<td>" . $alert['id'] . "</td>";
            echo "<td>" . $alert['type'] . "</td>";
            echo "<td>" . htmlspecialchars($alert['message']) . "</td>";
            echo "<td>" . $alert['status'] . "</td>";
            echo "<td>" . $alert['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No alerts found in database.</p>";
    }
    
    // Test the notification count logic
    echo "<h3>Notification Count Logic Test:</h3>";
    $lastAlertVisit = isset($_SESSION['last_alert_visit']) ? $_SESSION['last_alert_visit'] : date('Y-m-d H:i:s', strtotime('-24 hours'));
    echo "<p>Using last alert visit time: " . $lastAlertVisit . "</p>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE created_at > ? AND status = 'active'");
    $stmt->execute([$lastAlertVisit]);
    $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Alert count from query: " . $alertCount . "</p>";
    
    // Test the API response
    echo "<h3>API Response Test:</h3>";
    $apiResponse = [
        'success' => true,
        'transactionCount' => 0,
        'alertCount' => (int)$alertCount,
        'lastTransactionVisit' => $lastAlertVisit,
        'lastAlertVisit' => $lastAlertVisit
    ];
    echo "<pre>" . json_encode($apiResponse, JSON_PRETTY_PRINT) . "</pre>";
    
    // Test JavaScript console
    echo "<h3>JavaScript Test:</h3>";
    echo "<p>Check browser console for any JavaScript errors.</p>";
    echo "<p>Badge element should be: <span id='alert-badge' style='display: none;'>0</span></p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<script>
// Test the notification loading
console.log('Testing notification system...');

// Test the API call
fetch('get_notification_counts.php')
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API Response:', data);
        if (data.success) {
            console.log('Transaction count:', data.transactionCount);
            console.log('Alert count:', data.alertCount);
            
            // Test badge update
            const badge = document.getElementById('alert-badge');
            if (badge) {
                console.log('Badge element found:', badge);
                if (data.alertCount > 0) {
                    badge.textContent = data.alertCount;
                    badge.style.display = 'inline';
                    console.log('Badge updated with count:', data.alertCount);
                } else {
                    badge.style.display = 'none';
                    console.log('Badge hidden');
                }
            } else {
                console.log('Badge element NOT found!');
            }
        } else {
            console.log('API returned error:', data.error);
        }
    })
    .catch(error => {
        console.log('Fetch error:', error);
    });
</script>
