<?php
// Test script to verify PDF generation
echo "<h2>PDF Generation Test</h2>";

// Test if we can generate a simple PDF
echo "<h3>Testing Simple PDF Generation:</h3>";

// Create a test PDF content
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
$testPdf .= "4 0 obj\n";
$testPdf .= "<<\n";
$testPdf .= "/Length 50\n";
$testPdf .= ">>\n";
$testPdf .= "stream\n";
$testPdf .= "BT\n";
$testPdf .= "/F1 12 Tf\n";
$testPdf .= "50 750 Td\n";
$testPdf .= "(Test PDF) Tj\n";
$testPdf .= "ET\n";
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
$testFile = 'test_output.pdf';
file_put_contents($testFile, $testPdf);

if (file_exists($testFile)) {
    echo "<p style='color: green;'>✓ Test PDF created successfully: " . $testFile . "</p>";
    echo "<p>File size: " . filesize($testFile) . " bytes</p>";
    echo "<p><a href='$testFile' target='_blank'>Download Test PDF</a></p>";
} else {
    echo "<p style='color: red;'>✗ Failed to create test PDF</p>";
}

// Test database connection
echo "<h3>Testing Database Connection:</h3>";
try {
    require_once 'database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test reports table
    $stmt = $conn->query("SELECT COUNT(*) as count FROM reports");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Reports in database: " . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test report generation
echo "<h3>Testing Report Generation:</h3>";
try {
    $stmt = $conn->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 1");
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($report) {
        echo "<p style='color: green;'>✓ Found report: #" . $report['id'] . " (" . $report['type'] . ")</p>";
        echo "<p><a href='simple_pdf_generator.php?type=" . $report['type'] . "&id=" . $report['id'] . "' target='_blank'>Test PDF Generation</a></p>";
    } else {
        echo "<p style='color: orange;'>⚠ No reports found in database</p>";
        echo "<p>Generate a report first from the reports page</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error testing report generation: " . $e->getMessage() . "</p>";
}

echo "<h3>Debug Information:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";
echo "<p>File Permissions: " . (is_writable('.') ? 'Writable' : 'Not Writable') . "</p>";

// Check if wkhtmltopdf is available
echo "<h3>PDF Tools Check:</h3>";
if (function_exists('shell_exec')) {
    $wkhtmltopdf = shell_exec('which wkhtmltopdf 2>/dev/null');
    if ($wkhtmltopdf) {
        echo "<p style='color: green;'>✓ wkhtmltopdf available: " . trim($wkhtmltopdf) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ wkhtmltopdf not available - using fallback method</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ shell_exec disabled - using fallback method</p>";
}
?>
