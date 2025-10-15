<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['transaction_id']) || !isset($input['action'])) {
            throw new Exception('Missing required parameters');
        }
        
        $transactionId = (int)$input['transaction_id'];
        $action = $input['action']; // 'archive' or 'unarchive'
        
        if (!in_array($action, ['archive', 'unarchive'])) {
            throw new Exception('Invalid action');
        }
        
        $isArchived = ($action === 'archive') ? 1 : 0;
        
        // Check if is_archived column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM transactions LIKE 'is_archived'");
        if (!$checkColumn->fetch()) {
            throw new Exception('is_archived column does not exist. Please run: ALTER TABLE transactions ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER price_per_kg');
        }
        
        $stmt = $conn->prepare("UPDATE transactions SET is_archived = :is_archived WHERE id = :id");
        $stmt->bindParam(':is_archived', $isArchived, PDO::PARAM_INT);
        $stmt->bindParam(':id', $transactionId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->rowCount();
            if ($affectedRows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction ' . $action . 'd successfully',
                    'action' => $action,
                    'transaction_id' => $transactionId
                ]);
            } else {
                throw new Exception('Transaction not found or already in desired state');
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Failed to update transaction: ' . $errorInfo[2]);
        }
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
