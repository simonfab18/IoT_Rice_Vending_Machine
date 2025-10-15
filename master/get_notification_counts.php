<?php
session_start();
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get the last visit times from session or default to 24 hours ago
    $lastTransactionVisit = isset($_SESSION['last_transaction_visit']) ? $_SESSION['last_transaction_visit'] : date('Y-m-d H:i:s', strtotime('-24 hours'));
    $lastAlertVisit = isset($_SESSION['last_alert_visit']) ? $_SESSION['last_alert_visit'] : date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    // Get transaction count (new transactions since last visit)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE transaction_date > ?");
    $stmt->execute([$lastTransactionVisit]);
    $transactionCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get new alerts count (alerts created since last visit)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE created_at > ? AND status = 'active'");
    $stmt->execute([$lastAlertVisit]);
    $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'transactionCount' => (int)$transactionCount,
        'alertCount' => (int)$alertCount,
        'lastTransactionVisit' => $lastTransactionVisit,
        'lastAlertVisit' => $lastAlertVisit
    ]);
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
