<?php
require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle log submission from Arduino
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Fallback to form data if JSON parsing fails
            $input = $_POST;
        }
        
        if (isset($input['log_message'])) {
            $machineId = isset($input['machine_id']) ? $input['machine_id'] : 'rice_dispenser_1';
            $logLevel = isset($input['log_level']) ? strtoupper($input['log_level']) : 'INFO';
            $logMessage = $input['log_message'];
            $logCategory = isset($input['log_category']) ? $input['log_category'] : null;
            
            // Validate log level
            $validLevels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'SYSTEM'];
            if (!in_array($logLevel, $validLevels)) {
                $logLevel = 'INFO';
            }
            
            // Insert log entry
            $stmt = $conn->prepare("
                INSERT INTO arduino_logs (machine_id, log_level, log_message, log_category) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$machineId, $logLevel, $logMessage, $logCategory]);
            
            $logId = $conn->lastInsertId();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Log entry created successfully',
                'data' => [
                    'id' => $logId,
                    'machine_id' => $machineId,
                    'log_level' => $logLevel,
                    'log_message' => $logMessage,
                    'log_category' => $logCategory
                ]
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required field: log_message']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Handle log clearing
        $stmt = $conn->prepare("DELETE FROM arduino_logs");
        $stmt->execute();
        
        $deletedCount = $stmt->rowCount();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'All logs cleared successfully',
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle log retrieval for web interface
        $machineId = isset($_GET['machine_id']) ? $_GET['machine_id'] : 'rice_dispenser_1';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $logLevel = isset($_GET['log_level']) ? $_GET['log_level'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        
        // Build query
        $whereConditions = ['machine_id = ?'];
        $params = [$machineId];
        
        if ($logLevel) {
            $whereConditions[] = 'log_level = ?';
            $params[] = $logLevel;
        }
        
        if ($category) {
            $whereConditions[] = 'log_category = ?';
            $params[] = $category;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $stmt = $conn->prepare("
            SELECT id, machine_id, log_level, log_message, log_category, timestamp
            FROM arduino_logs 
            WHERE $whereClause
            ORDER BY timestamp DESC 
            LIMIT " . intval($limit) . " OFFSET " . intval($offset)
        );
        
        $stmt->execute($params);
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM arduino_logs WHERE $whereClause");
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'logs' => $logs,
                'pagination' => [
                    'total' => intval($totalCount),
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount
                ]
            ]
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
