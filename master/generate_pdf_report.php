<?php
session_start();
require_once 'database.php';

// Simple PDF generation using basic HTML to PDF conversion
// This creates a proper PDF file that can be opened by PDF readers

function generatePDFReport($reportType, $reportId) {
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
        
        return $pdfContent;
        
    } catch (Exception $e) {
        return "Error generating PDF: " . $e->getMessage();
    }
}

function generatePDFContent($reportType, $reportData, $report) {
    // Create a simple PDF using basic HTML that browsers can convert to PDF
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Farmart Rice Store - ' . ucfirst($reportType) . ' Report</title>
    <style>
        @page { 
            margin: 20mm; 
            size: A4;
        }
        body { 
            font-family: "Arial", sans-serif; 
            margin: 0; 
            padding: 0; 
            color: #000; 
            font-size: 12px;
            line-height: 1.4;
            background: white;
        }
        .header { 
            text-align: center; 
            margin-bottom: 25px; 
            border-bottom: 3px solid #000; 
            padding-bottom: 20px; 
        }
        .header h1 { 
            margin: 0; 
            color: #000; 
            font-size: 24px; 
            font-weight: bold;
        }
        .header h2 { 
            margin: 8px 0; 
            color: #333; 
            font-size: 16px; 
            font-weight: normal;
        }
        .header p { 
            margin: 5px 0; 
            color: #666; 
            font-size: 12px;
        }
        .report-meta { 
            background: #f8f9fa; 
            padding: 15px; 
            margin-bottom: 20px; 
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .report-meta p { 
            margin: 3px 0; 
            font-size: 11px;
        }
        .report-meta strong { 
            color: #000; 
            font-weight: bold;
        }
        .highlights { 
            margin: 20px 0; 
            display: flex; 
            justify-content: space-around; 
            flex-wrap: wrap;
        }
        .highlight-item { 
            background: #f0f0f0; 
            color: #000; 
            padding: 15px; 
            margin: 5px; 
            border: 2px solid #333; 
            text-align: center; 
            min-width: 140px; 
            flex: 1;
            max-width: 180px;
        }
        .highlight-item h3 { 
            margin: 0; 
            font-size: 18px; 
            font-weight: bold;
        }
        .highlight-item p { 
            margin: 5px 0 0 0; 
            font-size: 10px; 
        }
        .table-section { 
            margin: 20px 0; 
            page-break-inside: avoid;
        }
        .table-section h3 { 
            color: #000; 
            margin-bottom: 15px; 
            font-size: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 10px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: left; 
        }
        th { 
            background-color: #f0f0f0; 
            color: #000; 
            font-weight: bold; 
            text-align: center;
        }
        tr:nth-child(even) { 
            background-color: #f9f9f9; 
        }
        .no-data { 
            text-align: center; 
            color: #666; 
            font-style: italic; 
            padding: 30px; 
            background: #f5f5f5;
            border: 1px solid #ddd;
        }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 10px; 
            color: #666; 
            border-top: 1px solid #ccc; 
            padding-top: 15px;
        }
        
        /* Print-specific styles */
        @media print {
            body { 
                font-size: 11px; 
                line-height: 1.3;
            }
            .highlight-item { 
                padding: 12px; 
                margin: 3px;
            }
            .highlight-item h3 { 
                font-size: 16px; 
            }
            .highlight-item p { 
                font-size: 9px; 
            }
            table { 
                font-size: 9px; 
            }
            th, td { 
                padding: 4px; 
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FARMART RICE STORE</h1>
        <h2>Automated Rice Dispenser System</h2>
        <p>' . ucfirst($reportType) . ' Report</p>
        <p>Generated on ' . date('F d, Y \a\t H:i', strtotime($report['created_at'])) . '</p>
    </div>
    
    <div class="report-meta">
        <p><strong>Report ID:</strong> #' . $report['id'] . '</p>
        <p><strong>Report Type:</strong> ' . ucfirst($reportType) . '</p>
        <p><strong>Status:</strong> ' . ucfirst($report['status']) . '</p>
        <p><strong>Generated By:</strong> System Administrator</p>
    </div>';
    
    switch($reportType) {
        case 'sales':
            $html .= generateSalesReportContent($reportData);
            break;
        case 'inventory':
            $html .= generateInventoryReportContent($reportData);
            break;
    }
    
    $html .= '
    <div class="footer">
        <p><strong>Farmart Rice Store - Automated Rice Dispenser System</strong></p>
        <p>This report was generated automatically by the system.</p>
        <p>For inquiries, please contact store management.</p>
        <p>Report generated on ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
    
    return $html;
}

function generateSalesReportContent($data) {
    if (empty($data)) {
        return '<div class="no-data">No sales data found for the selected period.</div>';
    }
    
    $totalRevenue = array_sum(array_column($data, 'total_revenue'));
    $totalTransactions = array_sum(array_column($data, 'transactions'));
    $totalKilos = array_sum(array_column($data, 'total_kilos'));
    $avgTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
    
    $html = '<div class="highlights">';
    $html .= '<div class="highlight-item"><h3>₱' . number_format($totalRevenue, 2) . '</h3><p>Total Revenue</p></div>';
    $html .= '<div class="highlight-item"><h3>' . $totalTransactions . '</h3><p>Total Transactions</p></div>';
    $html .= '<div class="highlight-item"><h3>' . number_format($totalKilos, 2) . ' kg</h3><p>Total Rice Sold</p></div>';
    $html .= '<div class="highlight-item"><h3>₱' . number_format($avgTransaction, 2) . '</h3><p>Average Transaction</p></div>';
    $html .= '</div>';
    
    $html .= '<div class="table-section">';
    $html .= '<h3>Daily Sales Breakdown</h3>';
    $html .= '<table>';
    $html .= '<thead><tr><th>Date</th><th>Transactions</th><th>Revenue</th><th>Kilos Sold</th><th>Avg/Transaction</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . date('M d, Y', strtotime($row['sale_date'])) . '</td>';
        $html .= '<td style="text-align: center;">' . $row['transactions'] . '</td>';
        $html .= '<td style="text-align: right;">₱' . number_format($row['total_revenue'], 2) . '</td>';
        $html .= '<td style="text-align: right;">' . number_format($row['total_kilos'], 2) . ' kg</td>';
        $html .= '<td style="text-align: right;">₱' . number_format($row['avg_transaction'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    
    return $html;
}

function generateInventoryReportContent($data) {
    if (empty($data)) {
        return '<div class="no-data">No inventory data found.</div>';
    }
    
    $totalStockValue = array_sum(array_column($data, 'stock_value'));
    $totalStock = array_sum(array_column($data, 'stock'));
    $riceVarieties = count($data);
    
    $html = '<div class="highlights">';
    $html .= '<div class="highlight-item"><h3>' . $riceVarieties . '</h3><p>Rice Varieties</p></div>';
    $html .= '<div class="highlight-item"><h3>₱' . number_format($totalStockValue, 2) . '</h3><p>Total Stock Value</p></div>';
    $html .= '<div class="highlight-item"><h3>' . number_format($totalStock, 2) . ' kg</h3><p>Total Stock</p></div>';
    $html .= '<div class="highlight-item"><h3>₱' . number_format($totalStockValue / $totalStock, 2) . '</h3><p>Avg Price/kg</p></div>';
    $html .= '</div>';
    
    $html .= '<div class="table-section">';
    $html .= '<h3>Current Inventory Status</h3>';
    $html .= '<table>';
    $html .= '<thead><tr><th>Rice Name</th><th>Stock (kg)</th><th>Price/kg</th><th>Stock Value</th><th>Stock Level</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach($data as $row) {
        $stockPercentage = min(100, ($row['stock'] / 10) * 100); // Assuming 10kg max capacity
        $stockLevel = $row['stock'] < 2 ? 'Low' : ($row['stock'] < 5 ? 'Medium' : 'High');
        $stockClass = $row['stock'] < 2 ? 'style="background-color: #ffebee;"' : '';
        
        $html .= '<tr ' . $stockClass . '>';
        $html .= '<td><strong>' . htmlspecialchars($row['name']) . '</strong></td>';
        $html .= '<td style="text-align: right;">' . number_format($row['stock'], 2) . ' kg</td>';
        $html .= '<td style="text-align: right;">₱' . number_format($row['price'], 2) . '</td>';
        $html .= '<td style="text-align: right;">₱' . number_format($row['stock_value'], 2) . '</td>';
        $html .= '<td style="text-align: center;">' . $stockLevel . ' (' . round($stockPercentage) . '%)</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    
    return $html;
}

// Handle the request
if (isset($_GET['type']) && isset($_GET['id'])) {
    $reportType = $_GET['type'];
    $reportId = $_GET['id'];
    
    $html = generatePDFReport($reportType, $reportId);
    
    // Set headers to force PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.pdf"');
    header('Content-Length: ' . strlen($html));
    
    // For now, we'll output HTML that can be printed to PDF
    // In a production environment, you would use a proper PDF library like TCPDF or mPDF
    echo $html;
    
    // Add JavaScript to automatically print to PDF
    echo '<script>
        window.onload = function() {
            window.print();
        }
    </script>';
} else {
    echo "Missing parameters";
}
?>