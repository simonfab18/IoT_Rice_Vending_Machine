<?php
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>Reports in Database</h2>";
    
    $stmt = $conn->query("SELECT id, type, created_at, status FROM reports ORDER BY created_at DESC LIMIT 5");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reports)) {
        echo "<p>No reports found in database.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Type</th><th>Status</th><th>Created</th><th>Test PDF</th></tr>";
        
        foreach($reports as $report) {
            echo "<tr>";
            echo "<td>" . $report['id'] . "</td>";
            echo "<td>" . $report['type'] . "</td>";
            echo "<td>" . $report['status'] . "</td>";
            echo "<td>" . $report['created_at'] . "</td>";
            echo "<td><a href='simple_pdf_generator.php?type=" . $report['type'] . "&id=" . $report['id'] . "' target='_blank'>Download PDF</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
