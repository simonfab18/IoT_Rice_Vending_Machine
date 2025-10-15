<?php
session_start();
require_once 'database.php';


try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Track visit to transactions page
    $_SESSION['last_transaction_visit'] = date('Y-m-d H:i:s');
    
    // Pagination settings
    $recordsPerPage = 10;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    // Archive filter
    $showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';
    $archiveFilter = $showArchived ? 'WHERE is_archived = 1' : 'WHERE is_archived = 0';
    
    // Get total count of transactions (filtered by archive status)
    $countStmt = $conn->query("SELECT COUNT(*) as total FROM transactions $archiveFilter");
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    // Get transactions for current page (filtered by archive status)
    $stmt = $conn->prepare("SELECT *, 
                           CASE 
                               WHEN price_per_kg IS NOT NULL THEN price_per_kg 
                               ELSE 60.00 
                           END as price_per_kg 
                           FROM transactions 
                           $archiveFilter
                           ORDER BY transaction_date DESC 
                           LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}

// Function to generate pagination links
function generatePagination($currentPage, $totalPages, $baseUrl = '') {
    $pagination = '';
    
    if ($totalPages <= 1) {
        return $pagination;
    }
    
    $pagination .= '<div class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $pagination .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">&laquo; Previous</a>';
    } else {
        $pagination .= '<span class="disabled">&laquo; Previous</span>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $pagination .= '<a href="' . $baseUrl . '?page=1">1</a>';
        if ($startPage > 2) {
            $pagination .= '<span class="disabled">...</span>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $pagination .= '<span class="current">' . $i . '</span>';
        } else {
            $pagination .= '<a href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $pagination .= '<span class="disabled">...</span>';
        }
        $pagination .= '<a href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next &raquo;</a>';
    } else {
        $pagination .= '<span class="disabled">Next &raquo;</span>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Farmart Rice Store</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Remove any glow effects from dashboard header */
        .dashboard-header h1 {
            text-shadow: none !important;
            box-shadow: none !important;
            filter: none !important;
            -webkit-text-stroke: none !important;
            -webkit-filter: none !important;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .modal-body {
            padding: 30px;
        }

        .receipt {
            background: #f9f9f9;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 25px;
            font-family: 'Courier New', monospace;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }

        .receipt-subtitle {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .receipt-details {
            margin: 20px 0;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 5px 0;
        }

        .receipt-label {
            font-weight: bold;
            color: #333;
        }

        .receipt-value {
            color: #666;
        }

        .receipt-total {
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
            line-height: 1.4;
        }

        .receipt-footer p {
            margin: 5px 0;
        }

        .receipt-footer strong {
            color: #333;
            font-weight: bold;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 15px;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .print-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            width: 100%;
            transition: background 0.3s;
        }

        .print-btn:hover {
            background: #45a049;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            gap: 5px;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            background: white;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .pagination .current {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
            font-weight: bold;
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
            background: #f5f5f5;
        }

        .pagination .disabled:hover {
            background: #f5f5f5;
            color: #ccc;
            border-color: #ddd;
        }

        .pagination-info {
            text-align: center;
            margin: 10px 0;
            color: #666;
            font-size: 14px;
        }

        /* Archive Toggle Styles */
        .filter-controls {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .archive-toggle {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: nowrap;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #4CAF50;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .toggle-label {
            font-weight: 500;
            color: #333;
            white-space: nowrap;
            flex-shrink: 0;
            margin-left: 0;
        }


        /* Action Buttons Styles */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .archive-btn, .unarchive-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .archive-btn {
            background-color: #ffc107;
            color: #212529;
        }

        .archive-btn:hover {
            background-color: #e0a800;
        }

        .unarchive-btn {
            background-color: #17a2b8;
            color: white;
        }

        .unarchive-btn:hover {
            background-color: #138496;
        }

        .details-btn {
            background-color: #4CAF50;
            color: white;
        }

        .details-btn:hover {
            background-color: #45a049;
        }

        /* Confirmation Modal Styles */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
        }

        .confirmation-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: confirmationSlideIn 0.3s ease-out;
        }

        @keyframes confirmationSlideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .confirmation-header {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            text-align: center;
            position: relative;
        }

        .confirmation-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .confirmation-body {
            padding: 25px;
            text-align: center;
        }

        .confirmation-icon {
            font-size: 48px;
            color: #ffc107;
            margin-bottom: 15px;
        }

        .confirmation-message {
            font-size: 16px;
            color: #333;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .confirmation-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .confirmation-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .confirmation-btn.confirm {
            background-color: #ffc107;
            color: #212529;
        }

        .confirmation-btn.confirm:hover {
            background-color: #e0a800;
            transform: translateY(-1px);
        }

        .confirmation-btn.cancel {
            background-color: #6c757d;
            color: white;
        }

        .confirmation-btn.cancel:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .confirmation-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        @media print {
            .modal-header, .close, .print-btn, .pagination, .archive-toggle, .action-buttons, .confirmation-modal {
                display: none;
            }
            .modal-content {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: none;
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
        <header class="dashboard-header">
            <div>
                <h1>Transactions</h1>
                <p>View and manage all rice vending transactions.</p>
            </div>
        </header>


        <section class="transaction-filters">
            <div class="filter-controls">
                <div class="archive-toggle">
                    <span class="toggle-label">Show Archived</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="archiveToggle" <?php echo $showArchived ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <form id="filterForm">
                    <input type="text" id="searchInput" placeholder="Search by ID..." class="filter-input">
                    <input type="date" id="dateFilter" class="filter-input">
                    <button type="submit" class="action-btn"><i class="fa-solid fa-filter"></i> Filter</button>
                </form>
            </div>
        </section>

        <section class="transaction-logs full-page">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Rice Type</th>
                        <th>Amount</th>
                        <th>Kilos</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $transaction): ?>
                    <tr>
                        <td><?php 
                            $transactionDate = date('Ymd', strtotime($transaction['transaction_date']));
                            $transactionId = str_pad($transaction['id'], 3, '0', STR_PAD_LEFT);
                            echo "TXN-{$transactionDate}-{$transactionId}";
                        ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])); ?></td>
                        <td><?php echo htmlspecialchars($transaction['rice_name']); ?></td>
                        <td>₱<?php echo $transaction['amount']; ?></td>
                        <td><?php echo $transaction['kilos']; ?> kg</td>
                        <td class="action-buttons">
                            <button class="details-btn" onclick="viewDetails(<?php echo $transaction['id']; ?>, '<?php echo $transaction['amount']; ?>', '<?php echo $transaction['kilos']; ?>', '<?php echo date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])); ?>', '<?php echo htmlspecialchars($transaction['rice_name']); ?>', '<?php echo $transaction['price_per_kg']; ?>')"><i class="fa-solid fa-eye"></i> View</button>
                            <?php if ($transaction['is_archived']): ?>
                                <button class="unarchive-btn" onclick="toggleArchive(<?php echo $transaction['id']; ?>, 'unarchive')"><i class="fa-solid fa-box-open"></i> Unarchive</button>
                            <?php else: ?>
                                <button class="archive-btn" onclick="toggleArchive(<?php echo $transaction['id']; ?>, 'archive')"><i class="fa-solid fa-archive"></i> Archive</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Pagination Controls (Bottom) -->
        <?php 
        $paginationUrl = $showArchived ? '?archived=1' : '';
        echo generatePagination($currentPage, $totalPages, $paginationUrl); 
        ?>
    </main>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2><i class="fa-solid fa-receipt"></i> Transaction Receipt</h2>
            </div>
            <div class="modal-body">
                <div class="receipt">
                    <div class="receipt-header">
                        <h3 class="receipt-title">FARMART RICE STORE</h3>
                        <p class="receipt-subtitle">Automated Rice Dispenser</p>
                        <p class="receipt-subtitle">Transaction Receipt</p>
                    </div>
                    
                    <div class="receipt-details">
                        <div class="receipt-row">
                            <span class="receipt-label">Transaction ID:</span>
                            <span class="receipt-value" id="modalTransactionId"></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Date & Time:</span>
                            <span class="receipt-value" id="modalDateTime"></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Rice Type:</span>
                            <span class="receipt-value" id="modalRiceType"></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Rice Quantity:</span>
                            <span class="receipt-value" id="modalKilos"></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Price per kg:</span>
                            <span class="receipt-value" id="modalPricePerKg">₱60.00</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Total Amount:</span>
                            <span class="receipt-value" id="modalAmount"></span>
                        </div>
                    </div>
                    
                    <div class="receipt-total">
                        <div class="receipt-row">
                            <span class="receipt-label">TOTAL PAID:</span>
                            <span class="receipt-value" id="modalTotalAmount"></span>
                        </div>
                    </div>
                    
                    <div class="receipt-footer">
                        <p>Thank you for your purchase!</p>
                        <p>This is an automated transaction receipt.</p>
                        <p>For inquiries, please contact store management.</p>
                        <br>
                        <p><strong>VAT Exempt Sale under Sec. 109, NIRC</strong></p>
                        <br>
                        <p><strong>RETURN POLICY:</strong></p>
                        <p>Returns accepted within 3 days</p>
                        <p>with original receipt only.</p>
                    </div>
                </div>
                
                <button class="print-btn" onclick="printReceipt()">
                    <i class="fa-solid fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-content">
            <div class="confirmation-header">
                <h3 id="confirmationTitle">Confirm Action</h3>
            </div>
            <div class="confirmation-body">
                <div class="confirmation-icon">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                </div>
                <div class="confirmation-message" id="confirmationMessage">
                    Are you sure you want to perform this action?
                </div>
                <div class="confirmation-actions">
                    <button class="confirmation-btn confirm" id="confirmBtn">
                        <i class="fa-solid fa-check"></i> Confirm
                    </button>
                    <button class="confirmation-btn cancel" id="cancelBtn">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function viewDetails(id, amount, kilos, dateTime, riceType, pricePerKg) {
        // Generate professional transaction ID (same format as Arduino)
        const transactionDate = dateTime.split(' ')[0].replace(/-/g, '');
        const transactionId = String(id).padStart(3, '0');
        const professionalId = `TXN-${transactionDate}-${transactionId}`;
        
        // Populate modal with transaction data
        document.getElementById('modalTransactionId').textContent = professionalId;
        document.getElementById('modalDateTime').textContent = dateTime;
        document.getElementById('modalRiceType').textContent = riceType;
        document.getElementById('modalKilos').textContent = kilos + ' kg';
        document.getElementById('modalPricePerKg').textContent = '₱' + parseFloat(pricePerKg).toFixed(2);
        document.getElementById('modalAmount').textContent = '₱' + amount;
        document.getElementById('modalTotalAmount').textContent = '₱' + amount;
        
        // Show the modal
        document.getElementById('transactionModal').style.display = 'block';
        
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('transactionModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function printReceipt() {
        window.print();
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    function downloadCSV() {
        // Create CSV content
        let csv = 'Transaction ID,Date,Rice Type,Amount,Kilos\n';
        const rows = document.querySelectorAll('.logs-table tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const id = cells[0].textContent; // Already formatted as TXN-YYYYMMDD-XXX
            const date = cells[1].textContent;
            const riceType = cells[2].textContent;
            const amount = cells[3].textContent;
            const kilos = cells[4].textContent;
            
            csv += `${id},${date},${riceType},${amount},${kilos}\n`;
        });

        // Create and download CSV file
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'transactions.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // Filter functionality
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchValue = document.getElementById('searchInput').value.toLowerCase();
        const dateValue = document.getElementById('dateFilter').value;
        
        const rows = document.querySelectorAll('.logs-table tbody tr');
        
        rows.forEach(row => {
            const id = row.cells[0].textContent.toLowerCase();
            const date = row.cells[1].textContent;
            
            const matchesSearch = id.includes(searchValue);
            const matchesDate = !dateValue || date.includes(dateValue);
            
            row.style.display = matchesSearch && matchesDate ? '' : 'none';
        });
    });

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

    // Archive toggle functionality
    document.getElementById('archiveToggle').addEventListener('change', function() {
        const showArchived = this.checked;
        const currentUrl = new URL(window.location);
        
        if (showArchived) {
            currentUrl.searchParams.set('archived', '1');
        } else {
            currentUrl.searchParams.delete('archived');
        }
        
        window.location.href = currentUrl.toString();
    });

    // Archive/Unarchive functionality
    function toggleArchive(transactionId, action) {
        const actionText = action === 'archive' ? 'archive' : 'unarchive';
        const actionMessage = action === 'archive' 
            ? 'Are you sure you want to archive this transaction?\n\nArchived transactions will be moved to the archived view and can be restored later.'
            : 'Are you sure you want to unarchive this transaction?\n\nThis will restore the transaction to the active transactions list.';
        
        // Show confirmation modal
        showConfirmationModal(
            action === 'archive' ? 'Archive Transaction' : 'Unarchive Transaction',
            actionMessage.replace(/\n/g, '<br>'),
            () => {
                // Confirmed - proceed with archive/unarchive
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                button.disabled = true;

                fetch('archive_transaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        transaction_id: transactionId,
                        action: action
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showNotification(data.message, 'success');
                        
                        // Reload the page to reflect changes
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(data.message, 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while processing your request', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        );
    }

    // Confirmation modal functionality
    function showConfirmationModal(title, message, onConfirm) {
        document.getElementById('confirmationTitle').textContent = title;
        document.getElementById('confirmationMessage').innerHTML = message;
        
        const modal = document.getElementById('confirmationModal');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        // Clear previous event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        // Add new event listeners
        newConfirmBtn.addEventListener('click', () => {
            hideConfirmationModal();
            onConfirm();
        });
        
        newCancelBtn.addEventListener('click', hideConfirmationModal);
        
        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function hideConfirmationModal() {
        const modal = document.getElementById('confirmationModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close confirmation modal when clicking outside
    document.getElementById('confirmationModal').addEventListener('click', function(event) {
        if (event.target === this) {
            hideConfirmationModal();
        }
    });

    // Close confirmation modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const confirmationModal = document.getElementById('confirmationModal');
            if (confirmationModal.style.display === 'block') {
                hideConfirmationModal();
            }
        }
    });

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fa-solid fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add notification styles if not already added
        if (!document.getElementById('notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    animation: slideInRight 0.3s ease-out;
                    max-width: 400px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                .notification-success { background-color: #4CAF50; }
                .notification-error { background-color: #f44336; }
                .notification-info { background-color: #2196F3; }
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
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