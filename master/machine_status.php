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
    
    // Get machine status data (this would come from ESP32 in real implementation)
    // For now, we'll simulate the data
    
    // Simulate WiFi status
    $wifiStatus = 'online'; // 'online' or 'offline'
    $lastSeen = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    
    // Simulate machine power status
    $powerStatus = 'on'; // 'on' or 'off'
    $uptime = '72 hours, 15 minutes';
    
    // Simulate motor status
    $motorStatus = 'idle'; // 'running', 'idle', 'error'
    $lastDispense = date('Y-m-d H:i:s', strtotime('-2 minutes'));
    
    // Simulate sensor readings
    $temperature = 42.5; // °C
    $humidity = 65; // %
    $weightSensor = 0.0; // kg
    $levelSensor = 85; // % (rice level)
    
    // Simulate recent activity
    $recentActivity = [
        ['time' => '2 min ago', 'action' => 'Dispensed 2.5kg Regular Rice', 'status' => 'success'],
        ['time' => '5 min ago', 'action' => 'Dispensed 1.0kg Premium Rice', 'status' => 'success'],
        ['time' => '8 min ago', 'action' => 'Motor calibration', 'status' => 'info'],
        ['time' => '12 min ago', 'action' => 'Dispensed 3.0kg Regular Rice', 'status' => 'success']
    ];
    
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
    <title>Machine Status - Farmart Rice Store</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Machine Status Specific Styles */
        .status-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .status-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .status-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .status-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: none;
            border-left: 4px solid #4CAF50;
        }

        .status-card.offline {
            border-left-color: #f44336;
        }

        .status-card.warning {
            border-left-color: #ff9800;
        }

        .status-card.error {
            border-left-color: #f44336;
        }

        .status-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .status-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-indicator.online {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-indicator.offline {
            background: #ffebee;
            color: #c62828;
        }

        .status-indicator.on {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-indicator.off {
            background: #ffebee;
            color: #c62828;
        }

        .status-indicator.running {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-indicator.idle {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-indicator.error {
            background: #ffebee;
            color: #c62828;
        }

        .status-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .status-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .status-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .status-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .status-detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .sensor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .sensor-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .sensor-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .sensor-label {
            color: #666;
            font-size: 12px;
        }

        .activity-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: none;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .activity-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .refresh-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .refresh-btn:hover {
            background: #45a049;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }

        .activity-item.info {
            border-left-color: #2196F3;
        }

        .activity-item.warning {
            border-left-color: #ff9800;
        }

        .activity-item.error {
            border-left-color: #f44336;
        }

        .activity-content {
            flex: 1;
        }

        .activity-action {
            color: #333;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-time {
            color: #666;
            font-size: 12px;
        }

        .activity-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .activity-status.success {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .activity-status.info {
            background: #e3f2fd;
            color: #1565c0;
        }

        .activity-status.warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .activity-status.error {
            background: #ffebee;
            color: #c62828;
        }

        .control-panel {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: none;
            margin-bottom: 30px;
        }

        .control-header {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .control-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .control-btn {
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .control-btn.primary {
            background: #4CAF50;
            color: white;
        }

        .control-btn.primary:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .control-btn.danger {
            background: #f44336;
            color: white;
        }

        .control-btn.danger:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        .control-btn.warning {
            background: #ff9800;
            color: white;
        }

        .control-btn.warning:hover {
            background: #f57c00;
            transform: translateY(-2px);
        }

        .control-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 768px) {
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .control-buttons {
                grid-template-columns: 1fr;
            }
            
            .sensor-grid {
                grid-template-columns: repeat(2, 1fr);
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
        <header class="status-header">
            <h1><i class="fa-solid fa-cogs"></i> Machine Status</h1>
            <p>Monitor and control your rice vending machine in real-time</p>
        </header>

        <!-- Control Panel -->
        <section class="control-panel">
            <h2 class="control-header">
                <i class="fa-solid fa-gamepad"></i> Machine Control
            </h2>
            <div class="control-buttons">
                <button class="control-btn primary" onclick="sendCommand('power_toggle')">
                    <i class="fa-solid fa-power-off"></i>
                    Power Toggle
                </button>
                <button class="control-btn warning" onclick="sendCommand('maintenance_mode')">
                    <i class="fa-solid fa-tools"></i>
                    Maintenance Mode
                </button>
                <button class="control-btn primary" onclick="sendCommand('test_dispense')">
                    <i class="fa-solid fa-play"></i>
                    Test Dispense
                </button>
                <button class="control-btn danger" onclick="sendCommand('emergency_stop')">
                    <i class="fa-solid fa-stop"></i>
                    Emergency Stop
                </button>
                <button class="control-btn primary" onclick="sendCommand('calibrate')">
                    <i class="fa-solid fa-balance-scale"></i>
                    Calibrate Sensors
                </button>
                <button class="control-btn primary" onclick="refreshStatus()">
                    <i class="fa-solid fa-sync-alt"></i>
                    Refresh Status
                </button>
            </div>
        </section>

        <!-- Status Grid -->
        <section class="status-grid">
            <!-- WiFi Status -->
            <div class="status-card <?php echo $wifiStatus === 'offline' ? 'offline' : ''; ?>">
                <div class="status-header-row">
                    <h3 class="status-title">
                        <i class="fa-solid fa-wifi"></i>
                        WiFi Connection
                    </h3>
                    <span class="status-indicator <?php echo $wifiStatus; ?>">
                        <?php echo ucfirst($wifiStatus); ?>
                    </span>
                </div>
                <div class="status-value"><?php echo ucfirst($wifiStatus); ?></div>
                <div class="status-label">Internet Connection Status</div>
                <div class="status-details">
                    <div class="status-detail-item">
                        <span class="detail-label">Last Seen:</span>
                        <span class="detail-value"><?php echo $lastSeen; ?></span>
                    </div>
                    <div class="status-detail-item">
                        <span class="detail-label">Signal Strength:</span>
                        <span class="detail-value">-45 dBm</span>
                    </div>
                    <div class="status-detail-item">
                        <span class="detail-label">IP Address:</span>
                        <span class="detail-value">192.168.1.100</span>
                    </div>
                </div>
            </div>

            <!-- Power Status -->
            <div class="status-card <?php echo $powerStatus === 'off' ? 'offline' : ''; ?>">
                <div class="status-header-row">
                    <h3 class="status-title">
                        <i class="fa-solid fa-bolt"></i>
                        Power Status
                    </h3>
                    <span class="status-indicator <?php echo $powerStatus; ?>">
                        <?php echo ucfirst($powerStatus); ?>
                    </span>
                </div>
                <div class="status-value"><?php echo ucfirst($powerStatus); ?></div>
                <div class="status-label">Machine Power State</div>
                <div class="status-details">
                    <div class="status-detail-item">
                        <span class="detail-label">Uptime:</span>
                        <span class="detail-value"><?php echo $uptime; ?></span>
                    </div>
                    <div class="status-detail-item">
                        <span class="detail-label">Voltage:</span>
                        <span class="detail-value">220V AC</span>
                    </div>
                    <div class="status-detail-item">
                        <span class="detail-label">Current:</span>
                        <span class="detail-value">2.1A</span>
                    </div>
                </div>
            </div>

            <!-- Motor Status -->
            <div class="status-card <?php echo $motorStatus === 'error' ? 'error' : ($motorStatus === 'running' ? '' : 'warning'); ?>">
                <div class="status-header-row">
                    <h3 class="status-title">
                        <i class="fa-solid fa-cog"></i>
                        Motor Status
                    </h3>
                    <span class="status-indicator <?php echo $motorStatus; ?>">
                        <?php echo ucfirst($motorStatus); ?>
                    </span>
                </div>
                <div class="status-value"><?php echo ucfirst($motorStatus); ?></div>
                <div class="status-label">Dispensing Motor State</div>
                <div class="status-details">
                    <div class="status-detail-item">
                        <span class="detail-label">Last Dispense:</span>
                        <span class="detail-value"><?php echo $lastDispense; ?></span>
                    </div>
                    <div class="status-detail-item">
                        <span class="detail-label">Motor Speed:</span>
                        <span class="detail-value">1200 RPM</span>
                    </div>
                    <div class="status-detail-item">
                        <span class="detail-label">Temperature:</span>
                        <span class="detail-value">45°C</span>
                    </div>
                </div>
            </div>

            <!-- Sensors -->
            <div class="status-card">
                <div class="status-header-row">
                    <h3 class="status-title">
                        <i class="fa-solid fa-microchip"></i>
                        Sensor Readings
                    </h3>
                    <span class="status-indicator online">Normal</span>
                </div>
                <div class="status-value">All Normal</div>
                <div class="status-label">Real-time Sensor Data</div>
                <div class="sensor-grid">
                    <div class="sensor-item">
                        <div class="sensor-value"><?php echo $temperature; ?>°C</div>
                        <div class="sensor-label">Temperature</div>
                    </div>
                    <div class="sensor-item">
                        <div class="sensor-value"><?php echo $humidity; ?>%</div>
                        <div class="sensor-label">Humidity</div>
                    </div>
                    <div class="sensor-item">
                        <div class="sensor-value"><?php echo $weightSensor; ?> kg</div>
                        <div class="sensor-label">Weight</div>
                    </div>
                    <div class="sensor-item">
                        <div class="sensor-value"><?php echo $levelSensor; ?>%</div>
                        <div class="sensor-label">Rice Level</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="activity-section">
            <div class="activity-header">
                <h2 class="activity-title">
                    <i class="fa-solid fa-history"></i>
                    Recent Activity
                </h2>
                <button class="refresh-btn" onclick="refreshActivity()">
                    <i class="fa-solid fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
            <div class="activity-list">
                <?php foreach($recentActivity as $activity): ?>
                <div class="activity-item <?php echo $activity['status']; ?>">
                    <div class="activity-content">
                        <div class="activity-action"><?php echo $activity['action']; ?></div>
                        <div class="activity-time"><?php echo $activity['time']; ?></div>
                    </div>
                    <span class="activity-status <?php echo $activity['status']; ?>">
                        <?php echo ucfirst($activity['status']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
    // Machine control functions
    function sendCommand(command) {
        // This would send commands to your ESP32
        console.log('Sending command:', command);
        
        // Simulate command sending
        const btn = event.target.closest('.control-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Show success message
            showNotification(`Command '${command}' sent successfully!`, 'success');
        }, 2000);
    }

    function refreshStatus() {
        // This would refresh the machine status
        console.log('Refreshing machine status...');
        
        const btn = event.target.closest('.control-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Simulate status update
            showNotification('Machine status updated!', 'success');
        }, 1500);
    }

    function refreshActivity() {
        // This would refresh the activity log
        console.log('Refreshing activity log...');
        
        const btn = event.target.closest('.refresh-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Simulate activity update
            showNotification('Activity log updated!', 'success');
        }, 1000);
    }

    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Auto-refresh status every 30 seconds
    setInterval(() => {
        // This would update the status automatically
        console.log('Auto-refreshing status...');
    }, 30000);

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

    <style>
    /* Notification styles */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        padding: 15px 20px;
        box-shadow: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 10000;
        border-left: 4px solid #4CAF50;
    }

    .notification.show {
        transform: translateX(0);
    }

    .notification.success {
        border-left-color: #4CAF50;
    }

    .notification.info {
        border-left-color: #2196F3;
    }

    .notification.warning {
        border-left-color: #ff9800;
    }

    .notification.error {
        border-left-color: #f44336;
    }

    .notification i {
        font-size: 18px;
        color: #4CAF50;
    }

    .notification.success i {
        color: #4CAF50;
    }

    .notification.info i {
        color: #2196F3;
    }

    .notification.warning i {
        color: #ff9800;
    }

    .notification.error i {
        color: #f44336;
    }
    </style>
</body>
</html> 