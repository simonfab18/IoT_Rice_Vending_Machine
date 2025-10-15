<?php
session_start();
require_once 'database.php';

function generatePDF($reportType, $reportId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Get the report data
        $stmt = $conn->prepare("SELECT * FROM reports WHERE id = ? AND type = ?");
        $stmt->execute([$reportId, $reportType]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            throw new Exception("Report not found");
        }
        
        $reportData = json_decode($report['data'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid report data: " . json_last_error_msg());
        }
        
        // Generate a simple text-based PDF
        $pdfContent = generateSimplePDF($reportType, $reportData, $report);
        
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($pdfContent));
        
        // Output PDF content
        echo $pdfContent;
        exit;
        
    } catch (Exception $e) {
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set error headers
        header('Content-Type: text/html; charset=UTF-8');
        
        echo "<!DOCTYPE html><html><head><title>PDF Generation Error</title></head><body>";
        echo "<h2>PDF Generation Error</h2>";
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><a href='reports.php'>Back to Reports</a></p>";
        echo "</body></html>";
        exit;
    }
}

function generateSimplePDF($reportType, $reportData, $report) {
    // Create a minimal but valid PDF
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Catalog\n";
    $pdf .= "/Pages 2 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    $pdf .= "2 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Pages\n";
    $pdf .= "/Kids [3 0 R]\n";
    $pdf .= "/Count 1\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Generate content
    $content = "BT\n";
    $content .= "/F1 16 Tf\n";
    $content .= "50 750 Td\n";
    $content .= "(FARMART RICE STORE) Tj\n";
    $content .= "0 -25 Td\n";
    $content .= "/F1 12 Tf\n";
    $content .= "(Automated Rice Dispenser System) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(" . ucfirst($reportType) . " Report) Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Generated: " . date('Y-m-d H:i:s') . ") Tj\n";
    $content .= "0 -30 Td\n";
    
    // Add report data based on type
    if ($reportType === 'sales') {
        $totalRevenue = array_sum(array_column($reportData, 'total_revenue'));
        $totalTransactions = array_sum(array_column($reportData, 'transactions'));
        $totalKilos = array_sum(array_column($reportData, 'total_kilos'));
        
        $content .= "0 -20 Td\n";
        $content .= "(SALES SUMMARY) Tj\n";
        $content .= "0 -15 Td\n";
        $content .= "(Total Revenue: P" . number_format($totalRevenue, 2) . ") Tj\n";
        $content .= "0 -12 Td\n";
        $content .= "(Total Transactions: " . $totalTransactions . ") Tj\n";
        $content .= "0 -12 Td\n";
        $content .= "(Total Kilos Sold: " . number_format($totalKilos, 2) . " kg) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(DAILY BREAKDOWN) Tj\n";
        
        foreach($reportData as $row) {
            $content .= "0 -12 Td\n";
            $content .= "(" . date('M d, Y', strtotime($row['sale_date'])) . ": " . $row['transactions'] . " transactions, P" . number_format($row['total_revenue'], 2) . ") Tj\n";
        }
        
    } elseif ($reportType === 'inventory') {
        $totalStockValue = array_sum(array_column($reportData, 'stock_value'));
        $riceVarieties = count($reportData);
        
        $content .= "0 -20 Td\n";
        $content .= "(INVENTORY SUMMARY) Tj\n";
        $content .= "0 -15 Td\n";
        $content .= "(Rice Varieties: " . $riceVarieties . ") Tj\n";
        $content .= "0 -12 Td\n";
        $content .= "(Total Stock Value: P" . number_format($totalStockValue, 2) . ") Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(INVENTORY DETAILS) Tj\n";
        
        foreach($reportData as $row) {
            $content .= "0 -12 Td\n";
            $content .= "(" . htmlspecialchars($row['name']) . ": " . number_format($row['stock'], 2) . " kg @ P" . number_format($row['price'], 2) . "/kg) Tj\n";
        }
    }
    
    $content .= "0 -30 Td\n";
    $content .= "(Report ID: #" . $report['id'] . ") Tj\n";
    $content .= "0 -12 Td\n";
    $content .= "(Status: " . ucfirst($report['status']) . ") Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Generated by Farmart Rice Store System) Tj\n";
    $content .= "ET\n";
    
    $pdf .= "3 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Page\n";
    $pdf .= "/Parent 2 0 R\n";
    $pdf .= "/MediaBox [0 0 612 792]\n";
    $pdf .= "/Contents 4 0 R\n";
    $pdf .= "/Resources << /Font << /F1 5 0 R >> >>\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    $pdf .= "4 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Length " . strlen($content) . "\n";
    $pdf .= ">>\n";
    $pdf .= "stream\n";
    $pdf .= $content;
    $pdf .= "endstream\n";
    $pdf .= "endobj\n";
    
    $pdf .= "5 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Font\n";
    $pdf .= "/Subtype /Type1\n";
    $pdf .= "/BaseFont /Helvetica\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    $pdf .= "xref\n";
    $pdf .= "0 6\n";
    $pdf .= "0000000000 65535 f \n";
    $pdf .= "0000000009 00000 n \n";
    $pdf .= "0000000058 00000 n \n";
    $pdf .= "0000000115 00000 n \n";
    $pdf .= "0000000274 00000 n \n";
    $pdf .= "0000000380 00000 n \n";
    $pdf .= "trailer\n";
    $pdf .= "<<\n";
    $pdf .= "/Size 6\n";
    $pdf .= "/Root 1 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "startxref\n";
    $pdf .= "500\n";
    $pdf .= "%%EOF\n";
    
    return $pdf;
}

// Handle the request
if (isset($_GET['type']) && isset($_GET['id'])) {
    $reportType = $_GET['type'];
    $reportId = $_GET['id'];
    
    generatePDF($reportType, $reportId);
} else {
    echo "Missing parameters";
}
?>
