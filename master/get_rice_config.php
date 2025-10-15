<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Fetch name, price, capacity, and expiration date for available rice items
    $stmt = $conn->query("SELECT id, name, price, unit, capacity, expiration_date FROM rice_inventory ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize expected mapping: regular and premium (fallback to first two if arbitrary types)
    $result = [
        'items' => [],
        'meta' => [
            'currency' => 'PHP',
            'unit' => 'kg'
        ]
    ];

    foreach ($rows as $row) {
        $result['items'][] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'price' => (float)$row['price'],
            'unit' => isset($row['unit']) ? (string)$row['unit'] : 'kg',
            'capacity' => isset($row['capacity']) ? (float)$row['capacity'] : 10.0,
            'expiration_date' => isset($row['expiration_date']) ? (string)$row['expiration_date'] : ''
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


