<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if required data is set in POST data
    if (isset($_POST['riceAStock']) && isset($_POST['riceBStock'])) {
        $riceAStock = floatval($_POST['riceAStock']);
        $riceBStock = floatval($_POST['riceBStock']);
        
        // Update rice A stock (assuming it's the first rice in inventory)
        $stmt = $conn->prepare("UPDATE rice_inventory SET stock = ? WHERE id = (SELECT id FROM rice_inventory ORDER BY id LIMIT 1)");
        $stmt->execute([$riceAStock]);
        
        // Update rice B stock (assuming it's the second rice in inventory)
        $stmt = $conn->prepare("UPDATE rice_inventory SET stock = ? WHERE id = (SELECT id FROM rice_inventory ORDER BY id LIMIT 1 OFFSET 1)");
        $stmt->execute([$riceBStock]);
        
        // Log machine heartbeat (machine is online and communicating)
        $machineId = 'rice_dispenser_1';
        $wifiSignal = isset($_POST['wifiSignal']) ? intval($_POST['wifiSignal']) : null;
        $systemUptime = isset($_POST['systemUptime']) ? intval($_POST['systemUptime']) : null;
        
        $stmt = $conn->prepare("
            INSERT INTO machine_heartbeat (machine_id, last_seen, status, wifi_signal, system_uptime) 
            VALUES (?, UTC_TIMESTAMP(), 'online', ?, ?) 
            ON DUPLICATE KEY UPDATE 
                last_seen = UTC_TIMESTAMP(), 
                status = 'online',
                wifi_signal = COALESCE(?, wifi_signal),
                system_uptime = COALESCE(?, system_uptime)
        ");
        $stmt->execute([$machineId, $wifiSignal, $systemUptime, $wifiSignal, $systemUptime]);
        
        // Send success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Inventory updated successfully',
            'data' => [
                'riceAStock' => $riceAStock,
                'riceBStock' => $riceBStock
            ]
        ]);
    } else {
        // Send error response if required data is not set
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required data: riceAStock or riceBStock']);
    }
} catch(PDOException $e) {
    // Send error response if database operation fails
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
