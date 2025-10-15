<?php
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "Database connection successful!<br>";
    
    // Test inserting a transaction
    $stmt = $conn->prepare("INSERT INTO transactions (amount, kilos, transaction_date) VALUES (:amount, :kilos, NOW())");
    $amount = 60;
    $kilos = 1.00;
    
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':kilos', $kilos);
    
    if ($stmt->execute()) {
        $transactionId = $conn->lastInsertId();
        echo "Transaction inserted successfully!<br>";
        echo "Transaction ID: " . $transactionId . "<br>";
        
        // Test the JSON response format
        $response = [
            'status' => 'success', 
            'message' => 'Transaction recorded successfully',
            'data' => [
                'id' => $transactionId,
                'amount' => $amount,
                'kilos' => $kilos
            ]
        ];
        
        echo "<br>JSON Response:<br>";
        echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
        
    } else {
        echo "Failed to insert transaction<br>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
