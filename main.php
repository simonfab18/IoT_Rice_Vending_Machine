<?php
session_start();
require_once 'database.php';
require_once 'email_notifications.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}


try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get total sales
    $stmt = $conn->query("SELECT SUM(amount) as total_sales FROM transactions");
    $totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;
    
    // Get total transactions
    $stmt = $conn->query("SELECT COUNT(*) as total_transactions FROM transactions");
    $totalTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['total_transactions'] ?? 0;
    
    // Get total kilos sold
    $stmt = $conn->query("SELECT SUM(kilos) as total_kilos FROM transactions");
    $totalKilos = $stmt->fetch(PDO::FETCH_ASSOC)['total_kilos'] ?? 0;
    
        // Get recent transactions (last 5) with rice names
    $stmt = $conn->query("SELECT * FROM transactions 
                         ORDER BY transaction_date DESC LIMIT 5");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get daily sales data for the last 7 days (replacing monthly)
    $stmt = $conn->query("
        SELECT 
            DATE(transaction_date) as sale_date,
            SUM(amount) as total_amount,
            COUNT(*) as transaction_count
        FROM transactions 
        WHERE transaction_date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)
        GROUP BY DATE(transaction_date)
        ORDER BY sale_date ASC
    ");
    $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get weekly sales data for the last 4 weeks
    $stmt = $conn->query("
        SELECT 
            YEARWEEK(transaction_date) as week_number,
            MIN(DATE(transaction_date)) as week_start,
            SUM(amount) as total_amount,
            COUNT(*) as transaction_count
        FROM transactions 
        WHERE transaction_date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 4 WEEK)
        GROUP BY YEARWEEK(transaction_date)
        ORDER BY week_start ASC
    ");
    $weeklySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get monthly sales data for the last 6 months
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(amount) as total_amount,
            COUNT(*) as transaction_count
        FROM transactions 
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get rice preferences using rice_name field directly from transactions
    $stmt = $conn->query("
        SELECT 
            rice_name,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            SUM(kilos) as total_kilos
        FROM transactions
        GROUP BY rice_name
        ORDER BY count DESC
    ");
    $ricePreferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active alerts with enhanced information
    $stmt = $conn->query("SELECT * FROM alerts WHERE status = 'active' ORDER BY created_at DESC");
    $activeAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for low inventory alerts
    $stmt = $conn->query("SELECT * FROM rice_inventory WHERE stock < 2.0 ORDER BY stock ASC");
    $lowInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create low inventory alerts if they don't exist
    foreach ($lowInventory as $rice) {
        $alertMessage = "Low stock alert: {$rice['name']} is running low (Current: {$rice['stock']} kg / 10 kg)";
        
        // Check if this alert already exists
        $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
        $stmt->execute(["%{$rice['name']}%running low%"]);
        $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
        
        if ($alertCount == 0) {
            // Create new low inventory alert
            $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'storage', 'active')");
            $stmt->execute([$alertMessage]);
            
            // Send email notification for low stock
            EmailNotifications::sendLowStockAlert($rice);
        }
    }
    
    // Check expiration alerts automatically
    try {
        $stmt = $conn->query("SELECT * FROM rice_inventory WHERE expiration_date IS NOT NULL ORDER BY expiration_date ASC");
        $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $today = new DateTime();
        
        foreach ($riceInventory as $rice) {
            if ($rice['expiration_date']) {
                $expirationDate = new DateTime($rice['expiration_date']);
                $daysLeft = $today->diff($expirationDate)->days;
                
                $riceName = $rice['name'];
                $alertMessage = '';
                $alertType = 'expiration';
                
                if ($expirationDate < $today) {
                    // Rice is expired
                    $alertMessage = "EXPIRED: {$riceName} has expired on " . $expirationDate->format('M d, Y') . " - Remove from inventory immediately!";
                    
                    // Check if this alert already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                    $stmt->execute(["%{$riceName}%expired%"]);
                    $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                    
                    if ($alertCount == 0) {
                        $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, ?, 'active')");
                        $stmt->execute([$alertMessage, $alertType]);
                        
                        // Send email notification for expired rice
                        EmailNotifications::sendExpirationAlert($rice, $daysLeft);
                    }
                    
                } elseif ($daysLeft <= 7) {
                    // Rice is expiring within 7 days
                    $alertMessage = "URGENT: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Consider discounting or removing!";
                    
                    // Check if this alert already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                    $stmt->execute(["%{$riceName}%expires in%"]);
                    $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                    
                    if ($alertCount == 0) {
                        $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, ?, 'active')");
                        $stmt->execute([$alertMessage, $alertType]);
                        
                        // Send email notification for urgent expiration
                        EmailNotifications::sendExpirationAlert($rice, $daysLeft);
                    }
                    
                } elseif ($daysLeft <= 30) {
                    // Rice is expiring within 30 days
                    $alertMessage = "WARNING: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Plan for restocking!";
                    
                    // Check if this alert already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                    $stmt->execute(["%{$riceName}%expires in%"]);
                    $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                    
                    if ($alertCount == 0) {
                        $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, ?, 'active')");
                        $stmt->execute([$alertMessage, $alertType]);
                        
                        // Send email notification for warning expiration
                        EmailNotifications::sendExpirationAlert($rice, $daysLeft);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Expiration check error in main.php: " . $e->getMessage());
    }
    
    // Refresh alerts after potential new ones
    $stmt = $conn->query("SELECT * FROM alerts WHERE status = 'active' ORDER BY created_at DESC");
    $activeAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get real inventory data for machine health status
    $stmt = $conn->query("SELECT name, stock, capacity FROM rice_inventory ORDER BY id");
    $inventoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate overall stock level percentage
    $totalStock = 0;
    $maxCapacity = 0;
    foreach ($inventoryData as $rice) {
        $totalStock += $rice['stock'];
        $maxCapacity += $rice['capacity'];
    }
    $overallStockPercentage = $maxCapacity > 0 ? round(($totalStock / $maxCapacity) * 100) : 0;
    
    // Determine machine status based on heartbeat data
    $stmt = $conn->query("SELECT * FROM machine_heartbeat WHERE machine_id = 'rice_dispenser_1' ORDER BY last_seen DESC LIMIT 1");
    $heartbeatData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $machineStatus = 'offline';
    $lastSeen = 'Never';
    $statusMessage = 'Machine has never connected';
    $wifiSignal = 0;
    $systemUptime = 0;
    
    if ($heartbeatData) {
        $lastHeartbeatTime = strtotime($heartbeatData['last_seen']);
        $timeDiff = time() - $lastHeartbeatTime;
        
        // Debug information (remove in production)
        $currentTime = date('Y-m-d H:i:s');
        $lastHeartbeatFormatted = date('Y-m-d H:i:s', $lastHeartbeatTime);
        $minutesDiff = round($timeDiff / 60, 1);
        
        $wifiSignal = $heartbeatData['wifi_signal'] ?? 0;
        $systemUptime = $heartbeatData['system_uptime'] ?? 0;
        
        // More realistic time thresholds based on heartbeat
        if ($timeDiff < 30) { // 30 seconds - very recent heartbeat
            $machineStatus = 'online';
            $lastSeen = date('Y-m-d H:i:s', $lastHeartbeatTime);
            $statusMessage = 'Machine is active and operational';
        } elseif ($timeDiff < 120) { // 2 minutes - recent heartbeat
            $machineStatus = 'idle';
            $lastSeen = date('Y-m-d H:i:s', $lastHeartbeatTime);
            $statusMessage = 'Machine is idle (recently active)';
        } elseif ($timeDiff < 300) { // 5 minutes - short offline
            $machineStatus = 'idle';
            $lastSeen = date('Y-m-d H:i:s', $lastHeartbeatTime);
            $statusMessage = 'Machine is idle (no recent activity)';
        } else {
            $machineStatus = 'offline';
            $lastSeen = date('Y-m-d H:i:s', $lastHeartbeatTime);
            $statusMessage = 'Machine appears to be offline';
        }
        
    }
    
    // Calculate uptime based on recent transactions and machine activity
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as transaction_count,
            COUNT(DISTINCT DATE(transaction_date)) as active_days,
            MAX(transaction_date) as last_activity
        FROM transactions 
        WHERE transaction_date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)
    ");
    $uptimeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate uptime based on activity frequency and recency
    $activeDays = $uptimeData['active_days'];
    $transactionCount = $uptimeData['transaction_count'];
    $lastActivity = $uptimeData['last_activity'];
    
    // Base uptime on active days (0-100%)
    $baseUptime = $activeDays > 0 ? min(100, round(($activeDays / 7) * 100)) : 0;
    
    // Adjust uptime based on transaction frequency (more transactions = higher uptime)
    $frequencyBonus = 0;
    if ($transactionCount > 0) {
        $avgTransactionsPerDay = $transactionCount / max(1, $activeDays);
        if ($avgTransactionsPerDay >= 5) {
            $frequencyBonus = 10; // High activity bonus
        } elseif ($avgTransactionsPerDay >= 2) {
            $frequencyBonus = 5; // Medium activity bonus
        }
    }
    
    // Check if machine was active recently (within last 24 hours)
    $recentActivityBonus = 0;
    if ($lastActivity) {
        $lastActivityTime = strtotime($lastActivity);
        $hoursSinceLastActivity = (time() - $lastActivityTime) / 3600;
        if ($hoursSinceLastActivity < 24) {
            $recentActivityBonus = 15; // Recent activity bonus
        } elseif ($hoursSinceLastActivity < 72) {
            $recentActivityBonus = 5; // Somewhat recent activity
        }
    }
    
    $uptimePercentage = min(100, $baseUptime + $frequencyBonus + $recentActivityBonus);
    
    $machineHealth = [
        'status' => $machineStatus,
        'last_seen' => $lastSeen,
        'status_message' => $statusMessage,
        'wifi_signal' => $wifiSignal,
        'system_uptime' => $systemUptime,
        'stock_level' => $overallStockPercentage,
        'uptime' => $uptimePercentage . '%',
        'inventory_data' => $inventoryData,
        'recent_transactions' => $uptimeData['transaction_count']
    ];
    
    // Check if we need to create a new storage alert (every 20 transactions)
    if ($totalTransactions > 0 && $totalTransactions % 20 == 0) {
        // Check if there's already an active storage alert
        $stmt = $conn->query("SELECT COUNT(*) as alert_count FROM alerts WHERE type = 'storage' AND status = 'active'");
        $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
        
        if ($alertCount == 0) {
            // Create new storage alert
            $stmt = $conn->prepare("INSERT INTO alerts (message, type) VALUES (?, 'storage')");
            $stmt->execute(["Rice storage is running low. Please refill the machine."]);
        }
    }
    
    // Format data for daily chart
    $dailyChartLabels = [];
    $dailyChartData = [];
    $dailyTransactionCounts = [];
    
    // Fill in missing days with 0 values
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dailyChartLabels[] = date('M d', strtotime($date));
        
        $found = false;
        foreach ($dailySales as $sale) {
            if ($sale['sale_date'] == $date) {
                $dailyChartData[] = $sale['total_amount'];
                $dailyTransactionCounts[] = $sale['transaction_count'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $dailyChartData[] = 0;
            $dailyTransactionCounts[] = 0;
        }
    }
    
    // Format data for weekly chart
    $weeklyChartLabels = [];
    $weeklyChartData = [];
    foreach($weeklySales as $sale) {
        $weeklyChartLabels[] = 'Week ' . date('M d', strtotime($sale['week_start']));
        $weeklyChartData[] = $sale['total_amount'];
    }
    
    // Format data for monthly chart
    $monthlyChartLabels = [];
    $monthlyChartData = [];
    foreach(array_reverse($monthlySales) as $sale) {
        $monthlyChartLabels[] = date('M Y', strtotime($sale['month'] . '-01'));
        $monthlyChartData[] = $sale['total_amount'];
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rice Vending Machine</title>
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

        /* Machine Health Status Styles */
        .machine-health-panel {
            background: var(--bg-primary);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid var(--accent-color);
            color: var(--text-primary);
        }

        .machine-health-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .machine-status-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }

        .status-message {
            font-size: 12px;
            color: var(--text-secondary);
            font-style: italic;
        }

        .machine-status {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-online {
            background: var(--accent-color);
        }

        .status-offline {
            background: var(--danger-color);
        }

        .status-maintenance {
            background: var(--warning-color);
        }

        .status-idle {
            background: var(--warning-color);
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .health-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .health-metric {
            text-align: center;
            padding: 15px;
            background: var(--bg-primary);
            border-radius: 10px;
            box-shadow: none;
            border: 1px solid var(--border-color);
        }

        .health-metric-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .health-metric-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        /* Rice Stock Card Styles */
        .rice-stock-card {
            transition: all 0.3s ease;
        }

        .rice-stock-card:hover {
            transform: translateY(-2px);
            box-shadow: none;
        }

        .rice-stock-card.low-stock {
            border-left-color: #f44336;
        }

        .rice-stock-card.medium-stock {
            border-left-color: #ff9800;
        }

        .rice-stock-card.high-stock {
            border-left-color: #4CAF50;
        }

        .rice-stock-card .stock-bar {
            background: var(--bg-tertiary);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .rice-stock-card.low-stock .stock-bar > div {
            background: linear-gradient(90deg, var(--danger-color), #d32f2f);
        }

        .rice-stock-card.medium-stock .stock-bar > div {
            background: linear-gradient(90deg, var(--warning-color), #f57c00);
        }

        .rice-stock-card.high-stock .stock-bar > div {
            background: linear-gradient(90deg, var(--accent-color), #45a049);
        }

        /* Enhanced Alerts Styles */
        .alerts-banner {
            margin-bottom: 30px;
        }

        .alerts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .alerts-header h2 {
            margin: 0;
            color: #333;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-all-alerts {
            background: var(--accent-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .view-all-alerts:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .alert-item {
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
            color: #2d3748;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 5px solid #ff6b6b;
            position: relative;
            overflow: hidden;
        }

        .alert-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff6b6b, #ee5a24);
        }

        .alert-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .alert-item.warning {
            background: linear-gradient(135deg, #fff8e1 0%, #ffffff 100%);
            color: #e65100;
            border-left-color: #ff9800;
        }

        .alert-item.warning::before {
            background: linear-gradient(90deg, #ff9800, #f57c00);
        }

        .alert-item.info {
            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
            color: #1565c0;
            border-left-color: #2196f3;
        }

        .alert-item.info::before {
            background: linear-gradient(90deg, #2196f3, #1976d2);
        }

        .alert-item.storage {
            background: linear-gradient(135deg, #fff3e0 0%, #ffffff 100%);
            color: #e65100;
            border-left-color: #ff9800;
        }

        .alert-item.storage::before {
            background: linear-gradient(90deg, #ff9800, #f57c00);
        }

        .alert-item.expiration {
            background: linear-gradient(135deg, #ffebee 0%, #ffffff 100%);
            color: #c62828;
            border-left-color: #f44336;
        }

        .alert-item.expiration::before {
            background: linear-gradient(90deg, #f44336, #d32f2f);
        }

        .alert-details {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .alert-icon {
            font-size: 20px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            color: white;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.3);
            flex-shrink: 0;
        }

        .alert-item.warning .alert-icon {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            box-shadow: 0 3px 10px rgba(255, 152, 0, 0.3);
        }

        .alert-item.info .alert-icon {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            box-shadow: 0 3px 10px rgba(33, 150, 243, 0.3);
        }

        .alert-item.storage .alert-icon {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            box-shadow: 0 3px 10px rgba(255, 152, 0, 0.3);
        }

        .alert-item.expiration .alert-icon {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            box-shadow: 0 3px 10px rgba(244, 67, 54, 0.3);
        }

        .alert-message {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .alert-time {
            font-size: 13px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .alert-urgency {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }

        .alert-item.warning .alert-urgency {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
        }

        .alert-item.info .alert-urgency {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
        }

        .alert-item.storage .alert-urgency {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
        }

        .alert-item.expiration .alert-urgency {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
        }

        .alert-actions {
            display: flex;
            gap: 10px;
        }

        .view-alerts {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 3px 10px rgba(76, 175, 80, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .view-alerts:hover {
            background: linear-gradient(135deg, #45a049, #388e3c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .alert-item.warning .view-alerts {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            box-shadow: 0 3px 10px rgba(255, 152, 0, 0.3);
        }

        .alert-item.warning .view-alerts:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.4);
        }

        .alert-item.info .view-alerts {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            box-shadow: 0 3px 10px rgba(33, 150, 243, 0.3);
        }

        .alert-item.info .view-alerts:hover {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
        }

        .alert-item.storage .view-alerts {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            box-shadow: 0 3px 10px rgba(255, 152, 0, 0.3);
        }

        .alert-item.storage .view-alerts:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.4);
        }

        .alert-item.expiration .view-alerts {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            box-shadow: 0 3px 10px rgba(244, 67, 54, 0.3);
        }

        .alert-item.expiration .view-alerts:hover {
            background: linear-gradient(135deg, #d32f2f, #c62828);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
        }

        /* Rice Preferences Styles */
        .rice-preferences-panel {
            background: var(--bg-primary);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: none;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .rice-preferences-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .rice-type-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg-tertiary);
            border-radius: 10px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .rice-type-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .rice-type-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .rice-type-regular {
            background: linear-gradient(135deg, var(--accent-color), #45a049);
        }

        .rice-type-premium {
            background: linear-gradient(135deg, var(--warning-color), #F57C00);
        }

        .rice-type-stats {
            text-align: right;
        }

        .rice-type-count {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
        }

        .rice-type-amount {
            font-size: 14px;
            color: var(--text-secondary);
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
            background-color: var(--bg-primary);
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: none;
            animation: modalSlideIn 0.3s ease-out;
            color: var(--text-primary);
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
            background: linear-gradient(135deg, var(--accent-color), #45a049);
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
            background: var(--bg-tertiary);
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 25px;
            font-family: 'Courier New', monospace;
            color: var(--text-primary);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px solid var(--text-primary);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            color: var(--text-primary);
        }

        .receipt-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
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
            color: var(--text-primary);
        }

        .receipt-value {
            color: var(--text-secondary);
        }

        .receipt-total {
            border-top: 2px solid var(--text-primary);
            padding-top: 15px;
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 12px;
        }

        .close {
            color: var(--text-muted);
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
            color: var(--text-primary);
            text-decoration: none;
            cursor: pointer;
        }

        .print-btn {
            background: var(--accent-color);
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
            background: var(--accent-hover);
        }

        @media print {
            .modal-header, .close, .print-btn {
                display: none;
            }
            .modal-content {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: none;
            }
        }

        @media (max-width: 768px) {
            .quick-actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
        });
    </script>
    <main class="main-content">
        <header class="dashboard-header">
            <div>
                <h1>Welcome back, Admin</h1>
                <p>Monitor your rice vending machine at a glance.</p>
            </div>
        </header>



        <?php if (!empty($activeAlerts)): ?>
        <section class="alerts-banner">
           
            <?php foreach($activeAlerts as $alert): ?>
            <div class="alert-item <?php echo $alert['type']; ?>">
                <div class="alert-details">
                    <i class="alert-icon fa-solid <?php echo $alert['type'] == 'storage' ? 'fa-box' : ($alert['type'] == 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'); ?>"></i>
                    <div>
                        <div class="alert-message"><?php echo $alert['message']; ?></div>
                        <div class="alert-time">
                            <i class="fa-solid fa-clock"></i> 
                            Created: <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                            <?php 
                            $timeDiff = time() - strtotime($alert['created_at']);
                            if ($timeDiff < 3600) {
                                echo ' <span class="alert-urgency">(Just now)</span>';
                            } elseif ($timeDiff < 86400) {
                                echo ' <span class="alert-urgency">(' . round($timeDiff / 3600) . ' hours ago)</span>';
                            } else {
                                echo ' <span class="alert-urgency">(' . round($timeDiff / 86400) . ' days ago)</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="alert-actions">
                    <a href="alerts.php" class="view-alerts">Manage</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Machine Health Status -->
        <section class="machine-health-panel">
            <div class="machine-health-header">
                <h2><i class="fa-solid fa-heartbeat"></i> Machine Health Status</h2>
                <div class="machine-status-info">
                    <div class="machine-status">
                        <div class="status-indicator status-<?php echo $machineHealth['status']; ?>"></div>
                        <span><?php echo ucfirst($machineHealth['status']); ?></span>
                    </div>
                    <div class="status-message"><?php echo $machineHealth['status_message']; ?></div>
                </div>
            </div>
            <div class="health-metrics">
                <div class="health-metric">
                    <div class="health-metric-value"><?php echo $machineHealth['stock_level']; ?>%</div>
                    <div class="health-metric-label">Overall Stock</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric-value"><?php echo $machineHealth['uptime']; ?></div>
                    <div class="health-metric-label">Uptime (7 days)</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric-value"><?php echo $machineHealth['recent_transactions']; ?></div>
                    <div class="health-metric-label">Recent Sales</div>
                </div>
            </div>
            
            <!-- Individual Rice Stock Levels -->
            <div style="margin-top: 25px;">
                <h3 style="margin: 0 0 15px 0; color: var(--text-primary); font-size: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-boxes-stacked"></i> Rice Inventory Status
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach($machineHealth['inventory_data'] as $rice): ?>
                    <?php 
                    $stockPercentage = min(100, ($rice['stock'] / $rice['capacity']) * 100); // Use actual capacity
                    $stockClass = $rice['stock'] < 2 ? 'low-stock' : ($rice['stock'] < 5 ? 'medium-stock' : 'high-stock');
                    ?>
                    <div class="rice-stock-card <?php echo $stockClass; ?>">
                        <div class="stock-info">
                            <div class="rice-name"><?php echo htmlspecialchars($rice['name']); ?></div>
                            <div class="stock-details"><?php echo number_format($rice['stock'], 1); ?>kg / <?php echo number_format($rice['capacity'], 1); ?>kg</div>
                        </div>
                        <div class="stock-bar">
                            <div class="stock-fill" style="height: 100%; width: <?php echo $stockPercentage; ?>%; transition: width 0.3s ease; border-radius: 10px;"></div>
                        </div>
                        <div class="stock-footer">
                            <span class="stock-label">Stock Level</span>
                            <span class="stock-percentage"><?php echo round($stockPercentage); ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="margin-top: 15px; font-size: 12px; color: var(--text-secondary);">
                <i class="fa-solid fa-clock"></i> Last activity: <?php echo $machineHealth['last_seen']; ?>
                <?php if ($machineHealth['status'] == 'online'): ?>
                    <span style="color: var(--accent-color); font-weight: 600;">(Active now)</span>
                <?php elseif ($machineHealth['status'] == 'idle'): ?>
                    <span style="color: var(--warning-color); font-weight: 600;">(Idle - <?php echo $lastTransaction ? round(($timeDiff / 60), 1) : '0'; ?> min ago)</span>
                <?php endif; ?>
            </div>
        </section>

        <section class="summary-cards">
            <div class="card">
                <h3>Total Sales</h3>
                <p class="card-value">₱<?php echo number_format($totalSales, 2); ?></p>
            </div>
            <div class="card">
                <h3>Total Transactions</h3>
                <p class="card-value"><?php echo $totalTransactions; ?></p>
            </div>
            <div class="card">
                <h3>Total Rice Sold</h3>
                <p class="card-value"><?php echo number_format($totalKilos, 2); ?> kg</p>
            </div>
            <div class="card">
                <h3>Average Sale</h3>
                <p class="card-value">₱<?php echo $totalTransactions > 0 ? number_format($totalSales / $totalTransactions, 2) : '0.00'; ?></p>
            </div>
        </section>


        <!-- Rice Type Preferences -->
        <section class="rice-preferences-panel">
            <div class="rice-preferences-header">
                <h2><i class="fa-solid fa-seedling"></i> Rice Type Preferences</h2>
                <span style="color: var(--text-secondary);">Customer preferences analysis</span>
            </div>
            <?php foreach($ricePreferences as $rice): ?>
            <div class="rice-type-item">
                <div class="rice-type-info">
                    <div class="rice-type-icon rice-type-regular">
                        <i class="fa-solid fa-seedling"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($rice['rice_name']); ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary);"><?php echo $rice['count']; ?> transactions</div>
                    </div>
                </div>
                <div class="rice-type-stats">
                    <div class="rice-type-count"><?php echo $rice['count']; ?> sales</div>
                    <div class="rice-type-amount">₱<?php echo number_format($rice['total_amount'], 2); ?></div>
                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo number_format($rice['total_kilos'], 2); ?> kg</div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <section class="dashboard-main-row">
            <section class="transaction-logs">
                <h2>Recent Transactions</h2>
                <table class="logs-table">
                                    <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Rice Type</th>
                        <th>Amount</th>
                        <th>Kilos</th>
                        <th>View</th>
                    </tr>
                </thead>
                    <tbody>
                        <?php foreach($recentTransactions as $transaction): ?>
                        <tr>
                            <td><?php 
                                $transactionDate = date('Ymd', strtotime($transaction['transaction_date']));
                                $transactionId = str_pad($transaction['id'], 3, '0', STR_PAD_LEFT);
                                echo "TXN-{$transactionDate}-{$transactionId}";
                            ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['rice_name']); ?></td>
                            <td>₱<?php echo number_format($transaction['amount'], 2); ?></td>
                            <td><?php echo $transaction['kilos']; ?> kg</td>
                            <td><button class="details-btn btn btn-primary btn-sm" onclick="viewDetails(<?php echo $transaction['id']; ?>, '<?php echo $transaction['amount']; ?>', '<?php echo $transaction['kilos']; ?>', '<?php echo date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])); ?>', '<?php echo htmlspecialchars($transaction['rice_name']); ?>')"><i class="fa-solid fa-eye"></i> View</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </section>


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
                            <span class="receipt-value" id="modalPricePerKg"></span>
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
                    </div>
                </div>
                
                <button class="print-btn" onclick="printReceipt()">
                    <i class="fa-solid fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <script>
    function viewDetails(id, amount, kilos, dateTime, riceType) {
        // Calculate price per kg
        const pricePerKg = (parseFloat(amount) / parseFloat(kilos)).toFixed(2);
        
        // Generate professional transaction ID (same format as Arduino)
        const transactionDate = dateTime.split(' ')[0].replace(/-/g, '');
        const transactionId = String(id).padStart(3, '0');
        const professionalId = `TXN-${transactionDate}-${transactionId}`;
        
        // Populate modal with transaction data
        document.getElementById('modalTransactionId').textContent = professionalId;
        document.getElementById('modalDateTime').textContent = dateTime;
        document.getElementById('modalRiceType').textContent = riceType;
        document.getElementById('modalKilos').textContent = kilos + ' kg';
        document.getElementById('modalPricePerKg').textContent = '₱' + pricePerKg;
        document.getElementById('modalAmount').textContent = '₱' + parseFloat(amount).toFixed(2);
        document.getElementById('modalTotalAmount').textContent = '₱' + parseFloat(amount).toFixed(2);
        
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