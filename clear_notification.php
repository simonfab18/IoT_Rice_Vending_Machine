<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $type = $_POST['type'];
        $currentTime = date('Y-m-d H:i:s');
        
        if ($type === 'transactions') {
            // Update the last visit time for transactions
            $_SESSION['last_transaction_visit'] = $currentTime;
            $response = ['success' => true, 'message' => 'Transaction notifications cleared'];
            
        } elseif ($type === 'alerts') {
            // Update the last visit time for alerts
            $_SESSION['last_alert_visit'] = $currentTime;
            $response = ['success' => true, 'message' => 'Alert notifications cleared'];
            
        } else {
            $response = ['success' => false, 'message' => 'Invalid notification type'];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
