<?php
session_start();
require_once 'database.php';

echo "<h2>Notification System Test</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check current session data
    echo "<h3>Current Session Data:</h3>";
    echo "<p>Last Transaction Visit: " . (isset($_SESSION['last_transaction_visit']) ? $_SESSION['last_transaction_visit'] : 'Not set') . "</p>";
    echo "<p>Last Alert Visit: " . (isset($_SESSION['last_alert_visit']) ? $_SESSION['last_alert_visit'] : 'Not set') . "</p>";
    
    // Check recent transactions
    echo "<h3>Recent Transactions (last 24 hours):</h3>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $recentTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Count: " . $recentTransactions . "</p>";
    
    // Check recent alerts
    echo "<h3>Recent Alerts (last 24 hours):</h3>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND status = 'active'");
    $stmt->execute();
    $recentAlerts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Count: " . $recentAlerts . "</p>";
    
    // Test notification counts
    echo "<h3>Notification Counts Test:</h3>";
    $lastTransactionVisit = isset($_SESSION['last_transaction_visit']) ? $_SESSION['last_transaction_visit'] : date('Y-m-d H:i:s', strtotime('-24 hours'));
    $lastAlertVisit = isset($_SESSION['last_alert_visit']) ? $_SESSION['last_alert_visit'] : date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE transaction_date > ?");
    $stmt->execute([$lastTransactionVisit]);
    $transactionCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE created_at > ? AND status = 'active'");
    $stmt->execute([$lastAlertVisit]);
    $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p>Transaction notifications: " . $transactionCount . "</p>";
    echo "<p>Alert notifications: " . $alertCount . "</p>";
    
    // Show recent transactions
    echo "<h3>Recent Transactions Details:</h3>";
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY transaction_date DESC LIMIT 5");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($transactions)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Rice Name</th><th>Amount</th><th>Kilos</th></tr>";
        foreach ($transactions as $transaction) {
            echo "<tr>";
            echo "<td>" . $transaction['id'] . "</td>";
            echo "<td>" . $transaction['transaction_date'] . "</td>";
            echo "<td>" . htmlspecialchars($transaction['rice_name']) . "</td>";
            echo "<td>₱" . number_format($transaction['amount'], 2) . "</td>";
            echo "<td>" . $transaction['kilos'] . " kg</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No recent transactions found.</p>";
    }
    
    // Show recent alerts
    echo "<h3>Recent Alerts Details:</h3>";
    $stmt = $conn->prepare("SELECT * FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($alerts)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Type</th><th>Message</th><th>Status</th><th>Created</th></tr>";
        foreach ($alerts as $alert) {
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
        echo "<p>No recent alerts found.</p>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
