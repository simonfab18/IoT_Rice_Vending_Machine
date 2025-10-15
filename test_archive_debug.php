<?php
// Test file to debug archive functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection and archive functionality...\n\n";

try {
    require_once 'database.php';
    echo "✓ Database.php loaded successfully\n";
    
    $db = Database::getInstance();
    echo "✓ Database instance created\n";
    
    $conn = $db->getConnection();
    echo "✓ Database connection established\n";
    
    // Test if transactions table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'transactions'");
    if ($stmt->fetch()) {
        echo "✓ Transactions table exists\n";
        
        // Check if is_archived column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM transactions LIKE 'is_archived'");
        if ($checkColumn->fetch()) {
            echo "✓ is_archived column exists\n";
        } else {
            echo "✗ is_archived column does not exist\n";
            echo "Need to run: ALTER TABLE transactions ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER price_per_kg\n";
        }
        
        // Get a sample transaction
        $stmt = $conn->query("SELECT id FROM transactions LIMIT 1");
        $sample = $stmt->fetch();
        if ($sample) {
            echo "✓ Sample transaction found (ID: " . $sample['id'] . ")\n";
            
            // Test archive update
            $testId = $sample['id'];
            $stmt = $conn->prepare("UPDATE transactions SET is_archived = 1 WHERE id = :id");
            $stmt->bindParam(':id', $testId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                echo "✓ Archive update test successful\n";
                
                // Revert the test
                $stmt = $conn->prepare("UPDATE transactions SET is_archived = 0 WHERE id = :id");
                $stmt->bindParam(':id', $testId, PDO::PARAM_INT);
                $stmt->execute();
                echo "✓ Test reverted successfully\n";
            } else {
                echo "✗ Archive update test failed\n";
                $errorInfo = $stmt->errorInfo();
                echo "Error: " . $errorInfo[2] . "\n";
            }
        } else {
            echo "✗ No transactions found in database\n";
        }
    } else {
        echo "✗ Transactions table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>