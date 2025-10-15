<?php
// Debug PDF generation
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PDF Generation Debug</h2>";

// Test with a specific report
$reportType = 'sales';
$reportId = 25;

echo "<p>Testing PDF generation for Report ID: $reportId, Type: $reportType</p>";

try {
    require_once 'database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get the report data
    $stmt = $conn->prepare("SELECT * FROM reports WHERE id = ? AND type = ?");
    $stmt->execute([$reportId, $reportType]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        echo "<p style='color: red;'>Report not found</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Report found</p>";
    echo "<p>Report data length: " . strlen($report['data']) . " characters</p>";
    
    $reportData = json_decode($report['data'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<p style='color: red;'>JSON decode error: " . json_last_error_msg() . "</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ JSON data parsed successfully</p>";
    echo "<p>Number of data rows: " . count($reportData) . "</p>";
    
    // Test PDF generation
    echo "<h3>Testing PDF Content Generation:</h3>";
    
    // Generate a simple test PDF
    $testPdf = "%PDF-1.4\n";
    $testPdf .= "1 0 obj\n";
    $testPdf .= "<<\n";
    $testPdf .= "/Type /Catalog\n";
    $testPdf .= "/Pages 2 0 R\n";
    $testPdf .= ">>\n";
    $testPdf .= "endobj\n";
    $testPdf .= "2 0 obj\n";
    $testPdf .= "<<\n";
    $testPdf .= "/Type /Pages\n";
    $testPdf .= "/Kids [3 0 R]\n";
    $testPdf .= "/Count 1\n";
    $testPdf .= ">>\n";
    $testPdf .= "endobj\n";
    $testPdf .= "3 0 obj\n";
    $testPdf .= "<<\n";
    $testPdf .= "/Type /Page\n";
    $testPdf .= "/Parent 2 0 R\n";
    $testPdf .= "/MediaBox [0 0 612 792]\n";
    $testPdf .= "/Contents 4 0 R\n";
    $testPdf .= "/Resources << /Font << /F1 5 0 R >> >>\n";
    $testPdf .= ">>\n";
    $testPdf .= "endobj\n";
    
    // Simple content
    $content = "BT\n";
    $content .= "/F1 12 Tf\n";
    $content .= "50 750 Td\n";
    $content .= "(FARMART RICE STORE) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(" . ucfirst($reportType) . " Report) Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Report ID: #" . $report['id'] . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Generated: " . date('Y-m-d H:i:s') . ") Tj\n";
    $content .= "ET\n";
    
    $testPdf .= "4 0 obj\n";
    $testPdf .= "<<\n";
    $testPdf .= "/Length " . strlen($content) . "\n";
    $testPdf .= ">>\n";
    $testPdf .= "stream\n";
    $testPdf .= $content;
    $testPdf .= "endstream\n";
    $testPdf .= "endobj\n";
    $testPdf .= "5 0 obj\n";
    $testPdf .= "<<\n";
    $testPdf .= "/Type /Font\n";
    $testPdf .= "/Subtype /Type1\n";
    $testPdf .= "/BaseFont /Helvetica\n";
    $testPdf .= ">>\n";
    $testPdf .= "endobj\n";
    $testPdf .= "xref\n";
    $testPdf .= "0 6\n";
    $testPdf .= "0000000000 65535 f \n";
    $testPdf .= "0000000009 00000 n \n";
    $testPdf .= "0000000058 00000 n \n";
    $testPdf .= "0000000115 00000 n \n";
    $testPdf .= "0000000274 00000 n \n";
    $testPdf .= "0000000380 00000 n \n";
    $testPdf .= "trailer\n";
    $testPdf .= "<<\n";
    $testPdf .= "/Size 6\n";
    $testPdf .= "/Root 1 0 R\n";
    $testPdf .= ">>\n";
    $testPdf .= "startxref\n";
    $testPdf .= "500\n";
    $testPdf .= "%%EOF\n";
    
    // Save test PDF
    $testFile = 'debug_test.pdf';
    file_put_contents($testFile, $testPdf);
    
    if (file_exists($testFile)) {
        echo "<p style='color: green;'>✓ Test PDF created: $testFile</p>";
        echo "<p>File size: " . filesize($testFile) . " bytes</p>";
        echo "<p><a href='$testFile' target='_blank'>Download Test PDF</a></p>";
        
        // Test the actual PDF generator
        echo "<h3>Testing Actual PDF Generator:</h3>";
        echo "<p><a href='simple_pdf_generator.php?type=$reportType&id=$reportId' target='_blank'>Download Actual PDF</a></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Failed to create test PDF</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
