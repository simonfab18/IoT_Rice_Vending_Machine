<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}


try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get report statistics
    $stmt = $conn->query("SELECT COUNT(*) as total_reports FROM reports");
    $totalReports = $stmt->fetch(PDO::FETCH_ASSOC)['total_reports'] ?? 0;
    
    // Get recent reports
    $stmt = $conn->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 5");
    $recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'generate_report':
                    $reportType = $_POST['report_type'];
                    $dateRange = $_POST['date_range'];
                    $reportData = generateReport($conn, $reportType, $dateRange);
                    $reportId = saveReport($conn, $reportType, $reportData);
                    
                    // Redirect to view the generated report
                    header('Location: reports.php?generated=' . $reportId . '&type=' . $reportType);
                    exit();
                    break;
                    

            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Helper functions
function generateReport($conn, $type, $dateRange = 30) {
    switch($type) {
        case 'sales':
            return generateSalesReport($conn, $dateRange);
        case 'inventory':
            return generateInventoryReport($conn);
        default:
            return [];
    }
}

function generateSalesReport($conn, $dateRange = 30) {
    $stmt = $conn->query("
        SELECT 
            DATE(transaction_date) as sale_date,
            COUNT(*) as transactions,
            SUM(amount) as total_revenue,
            SUM(kilos) as total_kilos,
            AVG(amount) as avg_transaction
        FROM transactions 
        WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL {$dateRange} DAY)
        GROUP BY DATE(transaction_date)
        ORDER BY sale_date DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateInventoryReport($conn) {
    $stmt = $conn->query("
        SELECT 
            name,
            type,
            stock,
            price,
            (stock * price) as stock_value
        FROM rice_inventory 
        ORDER BY stock ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function saveReport($conn, $type, $data) {
    $stmt = $conn->prepare("INSERT INTO reports (type, data, status, created_at) VALUES (?, ?, 'completed', NOW())");
    $stmt->execute([$type, json_encode($data)]);
    return $conn->lastInsertId();
}



function displayReport($type, $data, $report) {
    echo '<div class="report-summary">';
    echo '<div class="report-meta">';
    echo '<p><strong>Report ID:</strong> #' . $report['id'] . '</p>';
    echo '<p><strong>Generated:</strong> ' . date('F d, Y \a\t H:i', strtotime($report['created_at'])) . '</p>';
    echo '<p><strong>Status:</strong> <span class="status-badge ' . $report['status'] . '">' . ucfirst($report['status']) . '</span></p>';
    echo '</div>';
    
    echo '<div class="report-data">';
    switch($type) {
        case 'sales':
            displaySalesReport($data);
            break;
        case 'inventory':
            displayInventoryReport($data);
            break;
    }
    echo '</div>';
    echo '</div>';
}

function displaySalesReport($data) {
    if (empty($data)) {
        echo '<p class="no-data">No sales data found for the selected period.</p>';
        return;
    }
    
    $totalRevenue = array_sum(array_column($data, 'total_revenue'));
    $totalTransactions = array_sum(array_column($data, 'transactions'));
    $totalKilos = array_sum(array_column($data, 'total_kilos'));
    
    echo '<div class="report-highlights">';
    echo '<div class="highlight-item">';
    echo '<h3>₱' . number_format($totalRevenue, 2) . '</h3>';
    echo '<p>Total Revenue</p>';
    echo '</div>';
    echo '<div class="highlight-item">';
    echo '<h3>' . $totalTransactions . '</h3>';
    echo '<p>Total Transactions</p>';
    echo '</div>';
    echo '<div class="highlight-item">';
    echo '<h3>' . number_format($totalKilos, 2) . ' kg</h3>';
    echo '<p>Total Rice Sold</p>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="report-table-container">';
    echo '<h3>Daily Sales Breakdown</h3>';
    echo '<table class="report-table">';
    echo '<thead><tr><th>Date</th><th>Transactions</th><th>Revenue</th><th>Kilos</th><th>Average</th></tr></thead>';
    echo '<tbody>';
    foreach($data as $row) {
        echo '<tr>';
        echo '<td>' . date('M d, Y', strtotime($row['sale_date'])) . '</td>';
        echo '<td>' . $row['transactions'] . '</td>';
        echo '<td>₱' . number_format($row['total_revenue'], 2) . '</td>';
        echo '<td>' . number_format($row['total_kilos'], 2) . ' kg</td>';
        echo '<td>₱' . number_format($row['avg_transaction'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

function displayInventoryReport($data) {
    if (empty($data)) {
        echo '<p class="no-data">No inventory data found.</p>';
        return;
    }
    
    $totalStockValue = array_sum(array_column($data, 'stock_value'));
    
    echo '<div class="report-highlights">';
    echo '<div class="highlight-item">';
    echo '<h3>' . count($data) . '</h3>';
    echo '<p>Rice Varieties</p>';
    echo '</div>';
    echo '<div class="highlight-item">';
    echo '<h3>₱' . number_format($totalStockValue, 2) . '</h3>';
    echo '<p>Total Stock Value</p>';
    echo '</div>';
    echo '<div class="highlight-item">';
    echo '<h3>' . array_sum(array_column($data, 'stock')) . ' kg</h3>';
    echo '<p>Total Stock</p>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="report-table-container">';
    echo '<h3>Current Inventory Status</h3>';
    echo '<table class="report-table">';
    echo '<thead><tr><th>Rice Name</th><th>Type</th><th>Stock (kg)</th><th>Price/kg</th><th>Stock Value</th></tr></thead>';
    echo '<tbody>';
    foreach($data as $row) {
        $stockClass = $row['stock'] < 2 ? 'low-stock' : '';
        echo '<tr class="' . $stockClass . '">';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . ucfirst($row['type']) . '</td>';
        echo '<td>' . number_format($row['stock'], 2) . ' kg</td>';
        echo '<td>₱' . number_format($row['price'], 2) . '</td>';
        echo '<td>₱' . number_format($row['stock_value'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Farmart Rice Store</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Reports Specific Styles */
        .reports-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .reports-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .reports-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }


        .reports-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .main-reports {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-reports {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-templates {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .report-template {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .report-template:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }

        .report-template.selected {
            border-color: #4CAF50;
            background: #e8f5e8;
        }

        .report-template i {
            font-size: 24px;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        .report-template h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 16px;
        }

        .report-template p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }

        .schedule-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #45a049;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }



        .recent-reports {
            margin-top: 20px;
        }

        .report-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .report-type {
            font-weight: 600;
            color: #333;
        }

        .report-status {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .report-time {
            font-size: 12px;
            color: #666;
        }



        /* Report Viewer Styles */
        .report-viewer {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .report-viewer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }

        .report-actions {
            display: flex;
            gap: 15px;
        }

        .report-summary {
            margin-bottom: 30px;
        }

        .report-meta {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .report-meta p {
            margin: 0;
            color: #666;
        }

        .report-meta strong {
            color: #333;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.failed {
            background: #f8d7da;
            color: #721c24;
        }

        .report-highlights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .highlight-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .highlight-item h3 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: bold;
        }

        .highlight-item p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .report-table-container {
            margin-bottom: 30px;
        }

        .report-table-container h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .report-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }

        .report-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            color: #666;
        }

        .report-table tr:hover {
            background: #f8f9fa;
        }

        .report-table tr.low-stock {
            background: #fff3cd;
        }

        .report-table tr.low-stock td {
            color: #856404;
        }

        .report-details h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .metric-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #4CAF50;
        }

        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .report-templates {
                grid-template-columns: 1fr;
            }
            
            .report-viewer-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .report-actions {
                justify-content: center;
            }
            
            .report-meta {
                grid-template-columns: 1fr;
            }
            
            .highlight-item {
                padding: 20px;
            }
            
            .highlight-item h3 {
                font-size: 24px;
            }
        }

        @media print {
            .report-actions {
                display: none !important;
            }
            
            .report-viewer-header {
                border-bottom: 2px solid #333;
                margin-bottom: 20px;
            }
            
            .report-content {
                font-size: 12px;
            }
            
            .highlight-item {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            .report-table {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            .report-table th,
            .report-table td {
                padding: 6px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div id="sidebar-include"></div>
    <script>
    fetch('sidebar.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('sidebar-include').innerHTML = html;
            // Execute any scripts in the loaded HTML
            const scripts = document.getElementById('sidebar-include').querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                document.head.appendChild(newScript);
            });
            // Apply theme to the loaded sidebar
            if (typeof applyThemeToSidebar === 'function') {
                applyThemeToSidebar();
            }
        });
    </script>
    
    <main class="main-content">
        <header class="reports-header">
            <h1><i class="fa-solid fa-chart-bar"></i> Reports & Analytics</h1>
            <p>Generate, schedule, and manage comprehensive business reports</p>
        </header>


        <div class="reports-grid">
            <!-- Main Reports Section -->
            <section class="main-reports">
                <h2 class="section-title">
                    <i class="fa-solid fa-file-chart-line"></i> Report Generation
                </h2>
                
                <div class="report-templates">
                    <div class="report-template" data-type="sales">
                        <i class="fa-solid fa-chart-line"></i>
                        <h4>Sales Report</h4>
                        <p>Daily, weekly, monthly sales analysis</p>
                    </div>
                    <div class="report-template" data-type="inventory">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <h4>Inventory Report</h4>
                        <p>Stock levels, reorder recommendations</p>
                    </div>
                </div>

                <form method="POST" class="schedule-form">
                    <input type="hidden" name="action" value="generate_report">
                    <input type="hidden" name="report_type" id="selected_report_type">
                    
                    <div class="form-group">
                        <label>Report Type</label>
                        <input type="text" id="report_type_display" placeholder="Select a report template above" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Date Range</label>
                        <select name="date_range">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 3 months</option>
                            <option value="365">Last year</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="generate_btn" disabled>
                        <i class="fa-solid fa-play"></i> Generate Report
                    </button>
                </form>
            </section>

            <!-- Sidebar Section -->
            <section class="sidebar-reports">
                
                <!-- Recent Reports -->
                <div class="recent-reports">
                    <h3 class="section-title">
                        <i class="fa-solid fa-history"></i> Recent Reports
                    </h3>
                    <?php if (!empty($recentReports)): ?>
                        <?php foreach($recentReports as $report): ?>
                        <div class="report-item">
                            <div class="report-header">
                                <span class="report-type"><?php echo ucfirst($report['type']); ?></span>
                                <span class="report-status"><?php echo ucfirst($report['status']); ?></span>
                            </div>
                            <div class="report-time">
                                Generated: <?php echo date('M d, H:i', strtotime($report['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #666; text-align: center; font-style: italic;">No reports generated yet</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Report Viewer Section -->
        <?php if (isset($_GET['generated']) && isset($_GET['type'])): ?>
        <section class="report-viewer">
            <div class="report-viewer-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-eye"></i> Generated Report: <?php echo ucfirst($_GET['type']); ?>
                </h2>
                <div class="report-actions">
                    <button class="btn-primary" onclick="downloadReport('<?php echo $_GET['type']; ?>', '<?php echo $_GET['generated']; ?>')">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </button>
                    <button class="btn-secondary" onclick="printReport()">
                        <i class="fa-solid fa-print"></i> Print Report
                    </button>
                    <button class="btn-secondary" onclick="window.location.href='reports.php'">
                        <i class="fa-solid fa-arrow-left"></i> Back to Reports
                    </button>
                </div>
            </div>
            
            <div class="report-content">
                <?php
                $reportId = $_GET['generated'];
                $reportType = $_GET['type'];
                
                // Get the generated report data
                $stmt = $conn->prepare("SELECT * FROM reports WHERE id = ? AND type = ?");
                $stmt->execute([$reportId, $reportType]);
                $report = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($report) {
                    $reportData = json_decode($report['data'], true);
                    displayReport($reportType, $reportData, $report);
                } else {
                    echo '<p>Report not found.</p>';
                }
                ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <script>
    // Report template selection
    document.querySelectorAll('.report-template').forEach(template => {
        template.addEventListener('click', function() {
            // Remove previous selection
            document.querySelectorAll('.report-template').forEach(t => t.classList.remove('selected'));
            
            // Select current template
            this.classList.add('selected');
            
            // Update form
            const reportType = this.dataset.type;
            document.getElementById('selected_report_type').value = reportType;
            document.getElementById('report_type_display').value = this.querySelector('h4').textContent;
            document.getElementById('generate_btn').disabled = false;
        });
    });

    // Export functions
    function exportReport(format) {
        const reportType = document.getElementById('selected_report_type').value;
        if (!reportType) {
            alert('Please select a report template first');
            return;
        }
        
        // Simulate export (you can implement actual export logic here)
        alert(`Exporting ${reportType} report as ${format.toUpperCase()}`);
    }

    // Download report function
    function downloadReport(type, reportId) {
        // Create a download link for the report
        const link = document.createElement('a');
        link.href = `download_report.php?type=${type}&id=${reportId}&format=pdf`;
        link.download = `${type}_report_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Download report function - downloads as PDF
    function downloadReport(type, reportId) {
        // Create a download link for the report in PDF format
        const link = document.createElement('a');
        link.href = `simple_pdf_generator.php?type=${type}&id=${reportId}`;
        link.download = `${type}_report_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Print report function
    function printReport() {
        window.print();
    }

    // Notification system
    let notificationCounts = {
        transactions: 0,
        alerts: 0
    };

    // Load notification counts on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadNotificationCounts();
        // Update counts every 30 seconds
        setInterval(loadNotificationCounts, 30000);
    });

    // Function to load notification counts from server
    function loadNotificationCounts() {
        fetch('get_notification_counts.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge('transaction-badge', data.transactionCount);
                    updateNotificationBadge('alert-badge', data.alertCount);
                }
            })
            .catch(error => {
                console.log('Error loading notification counts:', error);
            });
    }

    // Function to update notification badge
    function updateNotificationBadge(badgeId, count) {
        const badge = document.getElementById(badgeId);
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // Function to mark section as visited when clicked
    function markAsVisited(type) {
        // Clear the badge immediately
        if (type === 'transactions') {
            updateNotificationBadge('transaction-badge', 0);
        } else if (type === 'alerts') {
            updateNotificationBadge('alert-badge', 0);
        }
        
        // Mark as visited on server
        fetch('clear_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'type=' + type
        })
        .then(response => response.json())
        .then(data => {
            console.log('Section marked as visited:', data);
        })
        .catch(error => {
            console.log('Error marking as visited:', error);
        });
    }

    // Sidebar active state
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarInterval = setInterval(() => {
            const sidebar = document.querySelector('.sidebar-menu');
            if (sidebar) {
                clearInterval(sidebarInterval);
                const links = sidebar.querySelectorAll('li a');
                links.forEach(link => {
                    if (window.location.pathname.endsWith(link.getAttribute('href'))) {
                        link.parentElement.classList.add('active');
                    } else {
                        link.parentElement.classList.remove('active');
                    }
                });
            }
        }, 50);
    });
    </script>
</body>
</html> 