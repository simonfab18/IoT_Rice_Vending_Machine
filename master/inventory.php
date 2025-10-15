<?php
session_start();
require_once 'database.php';
require_once 'email_notifications.php';


try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get current inventory data
    $stmt = $conn->query("SELECT * FROM rice_inventory ORDER BY id");
    $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no inventory exists, create default entries
    if (empty($riceInventory)) {
        $defaultRice = [
            ['name' => 'Dinorado Rice', 'price' => 60.00, 'stock' => 8.50, 'unit' => 'kg', 'capacity' => 25.00, 'manufacturer' => 'Golden Grains Corp', 'expiration_date' => '2025-12-31'],
            ['name' => 'Jasmine Rice', 'price' => 65.00, 'stock' => 7.25, 'unit' => 'kg', 'capacity' => 30.00, 'manufacturer' => 'Premium Rice Co', 'expiration_date' => date('Y-m-d')]
        ];
        
        foreach ($defaultRice as $rice) {
            $stmt = $conn->prepare("INSERT INTO rice_inventory (name, price, stock, unit, capacity, manufacturer, expiration_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())");
            $stmt->execute([$rice['name'], $rice['price'], $rice['stock'], $rice['unit'], $rice['capacity'], $rice['manufacturer'], $rice['expiration_date']]);
        }
        
        // Refresh the data
        $stmt = $conn->query("SELECT * FROM rice_inventory ORDER BY id");
        $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_rice':
                    $stmt = $conn->prepare("UPDATE rice_inventory SET name = ?, price = ?, capacity = ?, manufacturer = ?, expiration_date = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['price'],
                        $_POST['capacity'],
                        $_POST['manufacturer'],
                        $_POST['expiration_date'],
                        $_POST['rice_id']
                    ]);
                    
                    // Check if stock is low and create alert
                    $newStock = floatval($_POST['stock']);
                    if ($newStock < 2.0) {
                        $riceName = $_POST['name'];
                        $alertMessage = "Low stock alert: {$riceName} is running low (Current: {$newStock} kg / 10 kg)";
                        
                        // Check if this alert already exists
                        $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                        $stmt->execute(["%{$riceName}%running low%"]);
                        $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                        
                        if ($alertCount == 0) {
                            // Create new low inventory alert
                            $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'storage', 'active')");
                            $stmt->execute([$alertMessage]);
                            
                            // Send email notification for low stock
                            $riceData = [
                                'name' => $riceName,
                                'stock' => $newStock,
                                'capacity' => 10.0 // Default capacity
                            ];
                            EmailNotifications::sendLowStockAlert($riceData);
                        }
                    }
                    
                    // Check expiration date and create alert
                    if (isset($_POST['expiration_date']) && $_POST['expiration_date']) {
                        $expirationDate = new DateTime($_POST['expiration_date']);
                        $today = new DateTime();
                        $daysLeft = $today->diff($expirationDate)->days;
                        
                        if ($expirationDate < $today) {
                            // Rice is expired
                            $riceName = $_POST['name'];
                            $alertMessage = "EXPIRED: {$riceName} has expired on " . $expirationDate->format('M d, Y') . " - Remove from inventory immediately!";
                            
                            // Check if this alert already exists
                            $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                            $stmt->execute(["%{$riceName}%expired%"]);
                            $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                            
                            if ($alertCount == 0) {
                                $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'expiration', 'active')");
                                $stmt->execute([$alertMessage]);
                            }
                        } elseif ($daysLeft <= 7) {
                            // Rice is expiring within 7 days
                            $riceName = $_POST['name'];
                            $alertMessage = "URGENT: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Consider discounting or removing!";
                            
                            // Check if this alert already exists
                            $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                            $stmt->execute(["%{$riceName}%expires in%"]);
                            $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                            
                            if ($alertCount == 0) {
                                $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'expiration', 'active')");
                                $stmt->execute([$alertMessage]);
                            }
                        } elseif ($daysLeft <= 30) {
                            // Rice is expiring within 30 days
                            $riceName = $_POST['name'];
                            $alertMessage = "WARNING: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Plan for restocking!";
                            
                            // Check if this alert already exists
                            $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                            $stmt->execute(["%{$riceName}%expires in%"]);
                            $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                            
                            if ($alertCount == 0) {
                                $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'expiration', 'active')");
                                $stmt->execute([$alertMessage]);
                            }
                        }
                    }
                    break;
                    
                case 'add_rice':
                    $stmt = $conn->prepare("INSERT INTO rice_inventory (name, price, stock, unit, capacity, manufacturer, expiration_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['price'],
                        10.0, // Default stock to 10kg
                        'kg',  // Default unit to kg
                        $_POST['capacity'],
                        $_POST['manufacturer'],
                        $_POST['expiration_date']
                    ]);
                    
                    // Check expiration date for new rice and create alert
                    if (isset($_POST['expiration_date']) && $_POST['expiration_date']) {
                        $expirationDate = new DateTime($_POST['expiration_date']);
                        $today = new DateTime();
                        $daysLeft = $today->diff($expirationDate)->days;
                        
                        if ($expirationDate < $today) {
                            // Rice is expired
                            $riceName = $_POST['name'];
                            $alertMessage = "EXPIRED: {$riceName} has expired on " . $expirationDate->format('M d, Y') . " - Remove from inventory immediately!";
                            
                            $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'expiration', 'active')");
                            $stmt->execute([$alertMessage]);
                        } elseif ($daysLeft <= 7) {
                            // Rice is expiring within 7 days
                            $riceName = $_POST['name'];
                            $alertMessage = "URGENT: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Consider discounting or removing!";
                            
                            $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'expiration', 'active')");
                            $stmt->execute([$alertMessage]);
                        } elseif ($daysLeft <= 30) {
                            // Rice is expiring within 30 days
                            $riceName = $_POST['name'];
                            $alertMessage = "WARNING: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Plan for restocking!";
                            
                            $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, 'expiration', 'active')");
                            $stmt->execute([$alertMessage]);
                        }
                    }
                    break;
                    
                case 'delete_rice':
                    $stmt = $conn->prepare("DELETE FROM rice_inventory WHERE id = ?");
                    $stmt->execute([$_POST['rice_id']]);
                    break;
            }
            
            // Redirect to refresh the page
            header('Location: inventory.php');
            exit();
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Farmart Rice Store</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Remove any glow effects from inventory header */
        .inventory-header h1 {
            text-shadow: none !important;
            box-shadow: none !important;
            filter: none !important;
            -webkit-text-stroke: none !important;
            -webkit-filter: none !important;
        }

        /* Inventory Specific Styles */
        .inventory-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .inventory-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .inventory-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .stock-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stock-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: none;
            position: relative;
            overflow: hidden;
        }

        .stock-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4CAF50, #45a049);
        }

        .stock-card.premium::before {
            background: linear-gradient(90deg, #FF9800, #F57C00);
        }

        .stock-card.low-stock::before {
            background: linear-gradient(90deg, #f44336, #d32f2f);
        }

        .stock-card.no-stock::before {
            background: linear-gradient(90deg, #9e9e9e, #757575);
        }

        .stock-card.no-stock {
            opacity: 0.8;
        }

        .no-stock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 80px; /* Leave space for action buttons */
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px 15px 0 0;
            z-index: 10;
        }

        .no-stock-message {
            background: #f44336;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            box-shadow: none;
        }

        .expired-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 80px; /* Leave space for action buttons */
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px 15px 0 0;
            z-index: 10;
        }

        .expired-message {
            background: #9e9e9e;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            box-shadow: none;
        }

        .stock-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .rice-name {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .rice-type-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .rice-type-badge.premium {
            background: #fff3e0;
            color: #f57c00;
        }

        .stock-level-container {
            position: relative;
            margin: 20px 0;
        }

        .wave-container {
            width: 100%;
            height: 200px;
            background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px 15px 0 0;
            overflow: hidden;
            position: relative;
            margin: 20px 0;
            box-shadow: none;
            border: 2px solid #dee2e6;
            perspective: 1000px;
            transition: all 0.3s ease;
        }

        .wave-container:hover {
            box-shadow: none;
            transform: translateY(-2px);
        }

        .wave-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(180deg, #f4f1eb 0%, #e8e0d0 50%, #d4c4a8 100%);
            transition: height 2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
            transform-style: preserve-3d;
        }

        .wave-fill.premium {
            background: linear-gradient(180deg, #f8f4e6 0%, #f0e6d2 50%, #e6d4b8 100%);
        }

        .wave-fill.low {
            background: linear-gradient(180deg, #f8e6e6 0%, #f0d4d4 50%, #e6b8b8 100%);
        }

        .rice-surface {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: 
                radial-gradient(ellipse 60% 20% at 30% 20%, rgba(255, 255, 255, 0.4) 0%, transparent 50%),
                radial-gradient(ellipse 40% 15% at 70% 40%, rgba(255, 255, 255, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse 50% 25% at 50% 60%, rgba(255, 255, 255, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse 35% 18% at 20% 80%, rgba(255, 255, 255, 0.25) 0%, transparent 50%);
            border-radius: 15px 15px 0 0;
            animation: surfaceGlow 4s ease-in-out infinite;
        }

        @keyframes surfaceGlow {
            0%, 100% { filter: brightness(1) contrast(1); }
            50% { filter: brightness(1.1) contrast(1.1); }
        }

        .wave {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: 
                linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 20%, rgba(255, 255, 255, 0.6) 50%, rgba(255, 255, 255, 0.4) 80%, transparent 100%);
            border-radius: 15px 15px 0 0;
            transform-origin: center;
        }

        .wave1 {
            animation: wave3D 3s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            opacity: 0.9;
            z-index: 3;
        }

        .wave2 {
            animation: wave3D 3s cubic-bezier(0.4, 0, 0.2, 1) infinite 1s;
            opacity: 0.7;
            z-index: 2;
        }

        .wave3 {
            animation: wave3D 3s cubic-bezier(0.4, 0, 0.2, 1) infinite 2s;
            opacity: 0.5;
            z-index: 1;
        }

        @keyframes wave3D {
            0% { 
                transform: translateX(-100%) scaleY(1) rotateX(0deg) scaleZ(1);
                opacity: 0.4;
                filter: brightness(1);
            }
            20% { 
                transform: translateX(-60%) scaleY(1.15) rotateX(3deg) scaleZ(1.1);
                opacity: 0.7;
                filter: brightness(1.1);
            }
            40% { 
                transform: translateX(-20%) scaleY(1.25) rotateX(0deg) scaleZ(1.2);
                opacity: 0.9;
                filter: brightness(1.2);
            }
            60% { 
                transform: translateX(20%) scaleY(1.25) rotateX(-3deg) scaleZ(1.2);
                opacity: 0.7;
                filter: brightness(1.2);
            }
            80% { 
                transform: translateX(60%) scaleY(1.15) rotateX(0deg) scaleZ(1.1);
                opacity: 0.7;
                filter: brightness(1.1);
            }
            100% { 
                transform: translateX(100%) scaleY(1) rotateX(0deg) scaleZ(1);
                opacity: 0.4;
                filter: brightness(1);
            }
        }

        .stock-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
            color: #8B4513;
            text-shadow: none;
            z-index: 10;
            white-space: nowrap;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: none;
        }

        .rice-grains {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background-image: 
                radial-gradient(circle 2px at 20% 30%, rgba(139, 69, 19, 0.4) 0%, transparent 50%),
                radial-gradient(circle 1.5px at 60% 20%, rgba(139, 69, 19, 0.3) 0%, transparent 50%),
                radial-gradient(circle 2.5px at 80% 50%, rgba(139, 69, 19, 0.35) 0%, transparent 50%),
                radial-gradient(circle 1px at 40% 70%, rgba(139, 69, 19, 0.25) 0%, transparent 50%),
                radial-gradient(circle 2px at 70% 80%, rgba(139, 69, 19, 0.4) 0%, transparent 50%),
                radial-gradient(circle 1.8px at 10% 60%, rgba(139, 69, 19, 0.2) 0%, transparent 50%),
                radial-gradient(circle 2.2px at 90% 30%, rgba(139, 69, 19, 0.3) 0%, transparent 50%),
                radial-gradient(circle 1.3px at 50% 10%, rgba(139, 69, 19, 0.15) 0%, transparent 50%);
            border-radius: 15px 15px 0 0;
            pointer-events: none;
            animation: grainFloat 6s ease-in-out infinite;
        }

        @keyframes grainFloat {
            0%, 100% { transform: translateY(0px) scale(1); }
            25% { transform: translateY(-2px) scale(1.02); }
            50% { transform: translateY(-1px) scale(1.01); }
            75% { transform: translateY(-3px) scale(1.03); }
        }

        .stock-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .stock-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            position: relative;
            z-index: 20; /* Ensure buttons are above overlay */
        }

        .action-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            position: relative;
            z-index: 25; /* Higher than overlay */
        }

        .action-btn:hover {
            background: #45a049;
        }

        .action-btn.edit {
            background: #2196F3;
        }

        .action-btn.edit:hover {
            background: #1976D2;
        }

        .action-btn.delete {
            background: #f44336;
        }

        .action-btn.delete:hover {
            background: #d32f2f;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: none;
        }

        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background-color: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: none;
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
            box-shadow: none;
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

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .modal-form .form-group {
            margin-bottom: 0;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
        }

        .btn-primary:hover {
            background: #45a049;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #f44336;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #d32f2f;
            transform: translateY(-1px);
            box-shadow: none;
        }

        .btn-danger:active {
            transform: translateY(0);
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .stock-overview {
                grid-template-columns: 1fr;
            }
            
            .stock-actions {
                flex-direction: column;
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
        <header class="inventory-header">
            <h1><i class="fa-solid fa-boxes-stacked"></i> Inventory Management</h1>
            <p>Monitor and manage your rice inventory in real-time</p>
            <div style="margin-top: 15px;">
                <button class="action-btn" onclick="addNewRice()" style="background: #28a745; border: none; padding: 8px 16px; border-radius: 20px; color: white; cursor: pointer; font-size: 14px;">
                    <i class="fa-solid fa-plus"></i> Add New Rice
                </button>
            </div>
        </header>



        <!-- Stock Overview -->
        <section class="stock-overview">
            <?php foreach($riceInventory as $rice): 
                $stockPercentage = ($rice['stock'] / $rice['capacity']) * 100;
                $isLowStock = $rice['stock'] < 2;
                $isNoStock = $stockPercentage < 5.0; // Less than 5% capacity
                
                // Check if rice is expired
                $isExpired = false;
                if (isset($rice['expiration_date']) && $rice['expiration_date']) {
                    $expDate = new DateTime($rice['expiration_date']);
                    $today = new DateTime();
                    $isExpired = $expDate < $today;
                }
            ?>
            <div class="stock-card <?php echo $isNoStock ? 'no-stock' : ($isLowStock ? 'low-stock' : ''); ?>">
                <?php if ($isExpired): ?>
                <div class="expired-overlay">
                    <div class="expired-message">
                        <i class="fa-solid fa-ban"></i><br>
                        UNAVAILABLE<br>
                        <small>Rice has expired</small>
                    </div>
                </div>
                <?php elseif ($isNoStock): ?>
                <div class="no-stock-overlay">
                    <div class="no-stock-message">
                        <i class="fa-solid fa-exclamation-triangle"></i><br>
                        NO STOCK AVAILABLE<br>
                        <small>Please refill to continue sales</small>
                    </div>
                </div>
                <?php endif; ?>
                <div class="stock-header">
                    <div class="rice-name"><?php echo htmlspecialchars($rice['name']); ?></div>
                </div>
                
                <div class="stock-info">
                    <span>Price: ₱<?php echo number_format($rice['price'], 2); ?> per <?php echo $rice['unit']; ?></span>
                </div>
                
                <div class="rice-details" style="margin: 15px 0; padding: 15px; background: var(--bg-tertiary); border-radius: 8px; border-left: 4px solid var(--accent-color);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                        <div>
                            <strong style="color: var(--text-primary); font-size: 14px;">Manufacturer:</strong>
                            <span style="color: var(--text-secondary); margin-left: 5px;"><?php echo htmlspecialchars($rice['manufacturer'] ?? 'Not specified'); ?></span>
                        </div>
                        <div>
                            <strong style="color: var(--text-primary); font-size: 14px;">Capacity:</strong>
                            <span style="color: var(--text-secondary); margin-left: 5px;"><?php echo number_format($rice['capacity'], 1); ?> <?php echo $rice['unit']; ?></span>
                        </div>
                        <div>
                            <strong style="color: var(--text-primary); font-size: 14px;">Expiration Date:</strong>
                            <span style="color: var(--text-secondary); margin-left: 5px;">
                                <?php 
                                if (isset($rice['expiration_date']) && $rice['expiration_date']) {
                                    $expDate = new DateTime($rice['expiration_date']);
                                    $today = new DateTime();
                                    $daysLeft = $today->diff($expDate)->days;
                                    
                                    if ($expDate < $today) {
                                        echo '<span style="color: #f44336; font-weight: bold;">EXPIRED (' . $expDate->format('M d, Y') . ')</span>';
                                    } elseif ($daysLeft <= 30) {
                                        echo '<span style="color: #ff9800; font-weight: bold;">' . $expDate->format('M d, Y') . ' (' . $daysLeft . ' days left)</span>';
                                    } else {
                                        echo $expDate->format('M d, Y');
                                    }
                                } else {
                                    echo 'Not specified';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="stock-level-container">
                    <div class="wave-container">
                        <?php 
                        $percentage = min(100, ($rice['stock'] / $rice['capacity']) * 100); // Use actual capacity
                        $stockClass = $rice['stock'] < 2 ? 'low' : ''; // Low stock warning at 2kg
                        ?>
                        <div class="wave-fill <?php echo $stockClass; ?>" style="height: <?php echo $percentage; ?>%">
                            <div class="rice-surface"></div>
                            <div class="rice-grains"></div>
                            <div class="wave wave1"></div>
                            <div class="wave wave2"></div>
                            <div class="wave wave3"></div>
                        </div>
                        <div class="stock-percentage"><?php echo round($percentage); ?>%</div>
                    </div>
                    <div class="stock-info">
                        <span>Stock Level: <?php echo round($percentage); ?>%</span>
                        <span>Capacity: <?php echo number_format($rice['capacity'], 1); ?> <?php echo $rice['unit']; ?></span>
                    </div>
                </div>
                
                <div class="stock-actions">
                    <button class="action-btn edit" onclick="editRice(<?php echo $rice['id']; ?>, '<?php echo htmlspecialchars($rice['name']); ?>', <?php echo $rice['price']; ?>, <?php echo $rice['capacity']; ?>, '<?php echo htmlspecialchars($rice['manufacturer'] ?? ''); ?>', '<?php echo $rice['expiration_date'] ?? ''; ?>')">
                        <i class="fa-solid fa-edit"></i> Edit
                    </button>
                    <button class="action-btn delete" onclick="deleteRice(<?php echo $rice['id']; ?>, '<?php echo htmlspecialchars($rice['name']); ?>')">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
    </main>

    <!-- Edit Rice Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('editModal')">&times;</span>
                <h2><i class="fa-solid fa-edit"></i> Edit Rice</h2>
            </div>
            <div class="modal-body">
                <form class="modal-form" method="POST">
                    <input type="hidden" name="action" value="update_rice">
                    <input type="hidden" name="rice_id" id="edit_rice_id">
                    <div class="form-group">
                        <label for="edit_name">Rice Name</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">Price (₱)</label>
                        <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                        <small style="color: #666; margin-top: 5px;">Price per kg in Philippine Peso</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_capacity">Capacity (kg)</label>
                        <input type="number" id="edit_capacity" name="capacity" step="0.1" min="1" max="100" required>
                        <small style="color: #666; margin-top: 5px;">Maximum capacity of the rice container</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_manufacturer">Manufacturer</label>
                        <input type="text" id="edit_manufacturer" name="manufacturer" placeholder="e.g., Golden Grains Corp">
                        <small style="color: #666; margin-top: 5px;">Rice manufacturer or supplier</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_expiration_date">Expiration Date</label>
                        <input type="date" id="edit_expiration_date" name="expiration_date">
                        <small style="color: #666; margin-top: 5px;">Best before date for food safety</small>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Update Rice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add New Rice Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('addModal')">&times;</span>
                <h2><i class="fa-solid fa-plus"></i> Add New Rice</h2>
            </div>
            <div class="modal-body">
                <form class="modal-form" method="POST">
                    <input type="hidden" name="action" value="add_rice">
                    <div class="form-group">
                        <label for="add_name">Rice Name</label>
                        <input type="text" id="add_name" name="name" required placeholder="e.g., Basmati Rice">
                    </div>
                    <div class="form-group">
                        <label for="add_price">Price (₱)</label>
                        <input type="number" id="add_price" name="price" step="0.01" min="0" required placeholder="e.g., 75.00">
                        <small style="color: #666; margin-top: 5px;">Price per kg in Philippine Peso</small>
                    </div>
                    <div class="form-group">
                        <label for="add_capacity">Capacity (kg)</label>
                        <input type="number" id="add_capacity" name="capacity" step="0.1" min="1" max="100" required placeholder="e.g., 25.0">
                        <small style="color: #666; margin-top: 5px;">Maximum capacity of the rice container</small>
                    </div>
                    <div class="form-group">
                        <label for="add_manufacturer">Manufacturer</label>
                        <input type="text" id="add_manufacturer" name="manufacturer" required placeholder="e.g., Golden Grains Corp">
                        <small style="color: #666; margin-top: 5px;">Rice manufacturer or supplier</small>
                    </div>
                    <div class="form-group">
                        <label for="add_expiration_date">Expiration Date</label>
                        <input type="date" id="add_expiration_date" name="expiration_date" required>
                        <small style="color: #666; margin-top: 5px;">Best before date for food safety</small>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Add Rice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #f44336, #d32f2f);">
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                <h2><i class="fa-solid fa-exclamation-triangle"></i> Confirm Deletion</h2>
            </div>
            <div class="modal-body" style="text-align: center; padding: 40px 30px;">
                <div style="font-size: 64px; color: var(--danger-color); margin-bottom: 20px;">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 style="margin: 0 0 15px 0; color: var(--text-primary); font-size: 24px;">Delete Rice Variant</h3>
                <p style="margin: 0 0 25px 0; color: var(--text-secondary); font-size: 16px; line-height: 1.5;">
                    Are you sure you want to delete <strong id="deleteRiceName" style="color: var(--danger-color);"></strong>?<br>
                    This action cannot be undone and will permanently remove the rice from your inventory.
                </p>
                <div style="background: var(--bg-tertiary); border: 1px solid var(--warning-color); border-radius: 8px; padding: 15px; margin: 20px 0; text-align: left;">
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <i class="fa-solid fa-info-circle" style="color: var(--warning-color); margin-right: 8px;"></i>
                        <strong style="color: var(--warning-color);">Warning:</strong>
                    </div>
                    <ul style="margin: 0; padding-left: 20px; color: var(--warning-color); font-size: 14px;">
                        <li>All inventory data for this rice will be lost</li>
                        <li>This action cannot be reversed</li>
                        <li>Make sure no transactions are pending for this rice</li>
                    </ul>
                </div>
                <div class="modal-actions" style="margin-top: 30px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')" style="flex: 1; margin-right: 10px;">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn-danger" onclick="confirmDelete()" style="flex: 1; margin-left: 10px;">
                        <i class="fa-solid fa-trash"></i> Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>



    <script>
    // Modal functions
    function editRice(id, name, price, capacity, manufacturer, expirationDate) {
        document.getElementById('edit_rice_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit_capacity').value = capacity;
        document.getElementById('edit_manufacturer').value = manufacturer;
        document.getElementById('edit_expiration_date').value = expirationDate;
        document.getElementById('editModal').style.display = 'block';
    }

    function addNewRice() {
        // Clear form fields
        document.getElementById('add_name').value = '';
        document.getElementById('add_price').value = '';
        document.getElementById('add_capacity').value = '';
        document.getElementById('add_manufacturer').value = '';
        document.getElementById('add_expiration_date').value = '';
        document.getElementById('addModal').style.display = 'block';
    }

    // Global variables for delete confirmation
    let deleteRiceId = null;
    let deleteRiceName = '';

    function deleteRice(id, name) {
        deleteRiceId = id;
        deleteRiceName = name;
        document.getElementById('deleteRiceName').textContent = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function confirmDelete() {
        if (deleteRiceId) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_rice';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'rice_id';
            idInput.value = deleteRiceId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }


    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Close modals with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
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