<?php
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_POST['alert_id'])) {
    echo json_encode(['success' => false, 'message' => 'Alert ID is required']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("UPDATE alerts SET status = 'resolved', resolved_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['alert_id']]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 