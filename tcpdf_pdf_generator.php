<?php
session_start();
require_once 'database.php';

// Include TCPDF library (you may need to download and include it)
// For now, I'll create a simple PDF generator using basic PHP

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
            
            // Generate PDF content
        $pdfContent = generatePDFContent($reportType, $reportData, $report);
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Output PDF content
        echo $pdfContent;
            
        } catch (Exception $e) {
        echo "Error generating PDF: " . $e->getMessage();
    }
}

function generatePDFContent($reportType, $reportData, $report) {
    // Create a simple PDF using basic PHP (this is a minimal implementation)
    // In production, you should use a proper PDF library like TCPDF or FPDF
    
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
    
    // Page content
    $content = "BT\n";
    $content .= "/F1 12 Tf\n";
    $content .= "50 750 Td\n";
    $content .= "(FARMART RICE STORE) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "/F1 10 Tf\n";
    $content .= "(" . ucfirst($reportType) . " Report) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Generated: " . date('Y-m-d H:i:s') . ") Tj\n";
    $content .= "0 -30 Td\n";
    
    // Add report data
    if ($reportType === 'sales') {
        $totalRevenue = array_sum(array_column($reportData, 'total_revenue'));
        $totalTransactions = array_sum(array_column($reportData, 'transactions'));
        $totalKilos = array_sum(array_column($reportData, 'total_kilos'));
        
        $content .= "0 -20 Td\n";
        $content .= "(Total Revenue: ₱" . number_format($totalRevenue, 2) . ") Tj\n";
        $content .= "0 -15 Td\n";
        $content .= "(Total Transactions: " . $totalTransactions . ") Tj\n";
        $content .= "0 -15 Td\n";
        $content .= "(Total Kilos: " . number_format($totalKilos, 2) . " kg) Tj\n";
    } elseif ($reportType === 'inventory') {
        $totalStockValue = array_sum(array_column($reportData, 'stock_value'));
        $riceVarieties = count($reportData);
        
        $content .= "0 -20 Td\n";
        $content .= "(Rice Varieties: " . $riceVarieties . ") Tj\n";
        $content .= "0 -15 Td\n";
        $content .= "(Total Stock Value: ₱" . number_format($totalStockValue, 2) . ") Tj\n";
    }
    
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
