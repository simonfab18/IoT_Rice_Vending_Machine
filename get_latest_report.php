<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $reportType = $_GET['type'] ?? '';
    
    if (empty($reportType)) {
        echo json_encode(['success' => false, 'message' => 'Report type is required']);
        exit;
    }
    
    // Get the latest report for this type
    $stmt = $conn->prepare("SELECT id FROM reports WHERE type = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$reportType]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($report) {
        echo json_encode(['success' => true, 'report_id' => $report['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No report found for this type']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 