<?php
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "Database connection successful!";
} catch(PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
}
?> 