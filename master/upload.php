<?php
require_once 'database.php';
require_once 'email_notifications.php';

try {
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if required data is set in POST data
    if (isset($_POST['totalAmount']) && isset($_POST['riceName']) && isset($_POST['quantity'])) {
        $totalAmount = intval($_POST['totalAmount']);
        $riceName = $_POST['riceName'];
        $kilos = floatval($_POST['quantity']);
        $pricePerKg = isset($_POST['pricePerKg']) ? floatval($_POST['pricePerKg']) : 60.00; // Default to 60 if not provided
        
        // Prepare SQL statement with explicit timezone
        $stmt = $conn->prepare("INSERT INTO transactions (amount, kilos, rice_name, price_per_kg, transaction_date) VALUES (:amount, :kilos, :rice_name, :price_per_kg, UTC_TIMESTAMP())");
        
        // Bind parameters
        $stmt->bindParam(':amount', $totalAmount);
        $stmt->bindParam(':kilos', $kilos);
        $stmt->bindParam(':rice_name', $riceName);
        $stmt->bindParam(':price_per_kg', $pricePerKg);
        
        // Execute the statement
        $stmt->execute();
        
        // Get the transaction ID
        $transactionId = $conn->lastInsertId();
        
        // Prepare transaction data for email notification
        $transactionData = [
            'id' => $transactionId,
            'amount' => $totalAmount,
            'kilos' => $kilos,
            'rice_name' => $riceName,
            'price_per_kg' => $pricePerKg
        ];
        
        // Send email notification for new transaction
        try {
            $emailSent = EmailNotifications::sendTransactionNotification($transactionData);
            if (!$emailSent) {
                error_log("Failed to send transaction email for ID: " . $transactionId);
            } else {
                error_log("Transaction email sent successfully for ID: " . $transactionId);
            }
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
        }
        
        // Send success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Transaction recorded successfully',
            'data' => [
                'id' => $transactionId,
                'amount' => $totalAmount,
                'kilos' => $kilos,
                'rice_name' => $riceName
            ]
        ]);
    } else {
        // Send error response if required data is not set
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required data: totalAmount, riceName, or quantity']);
    }
} catch(PDOException $e) {
    // Send error response if database operation fails
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
