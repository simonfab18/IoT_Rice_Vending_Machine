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
    
    // Track visit to alerts page
    $_SESSION['last_alert_visit'] = date('Y-m-d H:i:s');
    
    // Get all active alerts - newest first (by ID to ensure newest always on top)
    $stmt = $conn->query("SELECT * FROM alerts WHERE status = 'active' ORDER BY id DESC");
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total transactions
    $stmt = $conn->query("SELECT COUNT(*) as total_transactions FROM transactions");
    $totalTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['total_transactions'] ?? 0;
    
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
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts - Rice Vending Machine</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Enhanced Alerts Styles */

        .inventory-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .alerts-section {
            width: 100%;
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

        .alerts-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .alert-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.3s ease;
            border-left: 5px solid #ddd;
            width: 100%;
            box-sizing: border-box;
        }

        .alert-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .alert-card.storage {
            border-left-color: #ff6b6b;
        }

        .alert-card.maintenance {
            border-left-color: #ffc107;
        }

        .alert-card.system {
            border-left-color: #17a2b8;
        }

        .alert-card.expiration {
            border-left-color: #ff5722;
        }

        .alert-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            min-width: 45px;
        }

        .alert-icon .fa-box {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        }

        .alert-icon .fa-tools {
            background: linear-gradient(135deg, #ffc107, #ff9800);
        }

        .alert-icon .fa-exclamation-circle {
            background: linear-gradient(135deg, #17a2b8, #0d6efd);
        }

        .alert-icon .fa-clock {
            background: linear-gradient(135deg, #ff5722, #d32f2f);
        }

        .alert-content {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .alert-header h3 {
            margin: 0;
            color: #333;
            font-size: 16px;
            flex: 1;
            min-width: 0;
        }

        .alert-type-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .alert-type-badge.storage {
            background: #ffe6e6;
            color: #d32f2f;
        }

        .alert-type-badge.maintenance {
            background: #fff3e0;
            color: #f57c00;
        }

        .alert-type-badge.system {
            background: #e3f2fd;
            color: #1976d2;
        }

        .alert-type-badge.expiration {
            background: #ffebee;
            color: #d32f2f;
        }

        .alert-message {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .alert-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .alert-time {
            color: #888;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .alert-urgency {
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .alert-urgency.high {
            background: #ffebee;
            color: #d32f2f;
        }

        .alert-urgency.medium {
            background: #fff3e0;
            color: #f57c00;
        }

        .alert-urgency.low {
            background: #e8f5e8;
            color: #388e3c;
        }

        .alert-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
            align-items: flex-start;
        }

        .resolve-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 18px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .resolve-btn:hover {
            background: #45a049;
            transform: translateY(-1px);
        }

        .no-alerts {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        .no-alerts i {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .no-alerts h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
        }

        .no-alerts p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding: 10px !important;
                width: 100% !important;
            }
            
            .inventory-header {
                padding: 20px;
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .inventory-header h1 {
                font-size: 24px;
            }
            
            .alerts-section {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .alerts-container {
                width: 100%;
                padding: 0;
                flex-direction: column;
                gap: 15px;
            }
            
            .alert-card {
                flex-direction: column;
                text-align: center;
                padding: 15px;
                width: 100%;
                min-width: auto;
            }
            
            .alert-header {
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            
            .alert-actions {
                justify-content: center;
                margin-top: 10px;
            }
            
            .alert-icon {
                align-self: center;
            }
        }

        @media (max-width: 480px) {
            .alert-card {
                padding: 12px;
                margin-bottom: 15px;
            }
            
            .alert-header h3 {
                font-size: 14px;
            }
            
            .alert-message {
                font-size: 14px;
            }
            
            .resolve-btn {
                padding: 6px 12px;
                font-size: 12px;
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
            <h1><i class="fa-solid fa-exclamation-triangle"></i> System Alerts</h1>
            <p>Monitor and manage system alerts and notifications</p>
            <div style="margin-top: 15px;">
                <span class="alert-count" style="background: var(--accent-color); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                    <i class="fa-solid fa-bell"></i> <?php echo count($alerts); ?> Active Alerts
                </span>
            </div>
        </header>

        <section class="alerts-section">
            <div class="alerts-container">
                <?php if (empty($alerts)): ?>
                    <div class="no-alerts">
                        <i class="fa-solid fa-check-circle"></i>
                        <h3>All Clear!</h3>
                        <p>No active alerts at the moment. Your system is running smoothly.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($alerts as $alert): ?>
                        <div class="alert-card <?php echo $alert['type']; ?>">
                            <div class="alert-icon">
                                <?php if($alert['type'] == 'storage'): ?>
                                    <i class="fa-solid fa-box"></i>
                                <?php elseif($alert['type'] == 'maintenance'): ?>
                                    <i class="fa-solid fa-tools"></i>
                                <?php elseif($alert['type'] == 'expiration'): ?>
                                    <i class="fa-solid fa-clock"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-exclamation-circle"></i>
                                <?php endif; ?>
                            </div>
                            <div class="alert-content">
                                <div class="alert-header">
                                    <h3><?php echo ucfirst($alert['type']); ?> Alert</h3>
                                    <span class="alert-type-badge <?php echo $alert['type']; ?>"><?php echo ucfirst($alert['type']); ?></span>
                                </div>
                                <p class="alert-message"><?php echo $alert['message']; ?></p>
                                <div class="alert-details">
                                    <span class="alert-time">
                                        <i class="fa-solid fa-clock"></i> 
                                        Created: <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                                    </span>
                                    <?php 
                                    $timeDiff = time() - strtotime($alert['created_at']);
                                    if ($timeDiff < 3600) {
                                        echo '<span class="alert-urgency high">High Priority (Just now)</span>';
                                    } elseif ($timeDiff < 86400) {
                                        echo '<span class="alert-urgency medium">Medium Priority (' . round($timeDiff / 3600) . ' hours ago)</span>';
                                    } else {
                                        echo '<span class="alert-urgency low">Low Priority (' . round($timeDiff / 86400) . ' days ago)</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="alert-actions">
                                <button class="resolve-btn" onclick="resolveAlert(<?php echo $alert['id']; ?>)">
                                    <i class="fa-solid fa-check"></i> Resolve
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
    // Display error message if there's a database error
    <?php if (isset($error_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            alert('Error: <?php echo addslashes($error_message); ?>');
        });
    <?php endif; ?>

    function resolveAlert(alertId) {
        showConfirmDialog('Resolve Alert', 'Are you sure you want to resolve this alert? This action cannot be undone.', function() {
            // Show loading message
            const resolveBtn = document.querySelector(`button[onclick="resolveAlert(${alertId})"]`);
            const originalText = resolveBtn.innerHTML;
            resolveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resolving...';
            resolveBtn.disabled = true;
            
            fetch('resolve_alert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'alert_id=' + alertId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlertMessage('success', 'Alert resolved successfully!', 'The alert has been marked as resolved.');
                    // Reload page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    showAlertMessage('error', 'Failed to resolve alert', data.message || 'An unknown error occurred.');
                    // Restore button
                    resolveBtn.innerHTML = originalText;
                    resolveBtn.disabled = false;
                }
            })
            .catch(error => {
                // Show error message
                showAlertMessage('error', 'Network Error', 'Unable to connect to server. Please check your connection.');
                // Restore button
                resolveBtn.innerHTML = originalText;
                resolveBtn.disabled = false;
            });
        });
    }

    // Function to show custom confirmation dialog
    function showConfirmDialog(title, message, onConfirm) {
        // Create modal overlay
        const modalOverlay = document.createElement('div');
        modalOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        `;

        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 0;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideInScale 0.3s ease-out;
            overflow: hidden;
        `;

        modalContent.innerHTML = `
            <div style="background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; padding: 20px; text-align: center;">
                <i class="fa-solid fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i>
                <h3 style="margin: 0; font-size: 18px; font-weight: 600;">${title}</h3>
            </div>
            <div style="padding: 25px;">
                <p style="margin: 0 0 25px 0; color: #333; line-height: 1.5; text-align: center;">${message}</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button id="confirm-cancel" style="
                        background: #6c757d;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 8px;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        min-width: 100px;
                    " onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                    <button id="confirm-ok" style="
                        background: #ff6b6b;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 8px;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        min-width: 100px;
                    " onmouseover="this.style.background='#ee5a24'" onmouseout="this.style.background='#ff6b6b'">
                        <i class="fa-solid fa-check"></i> Confirm
                    </button>
                </div>
            </div>
        `;

        modalOverlay.appendChild(modalContent);
        document.body.appendChild(modalOverlay);

        // Add CSS animations
        if (!document.getElementById('modal-animations')) {
            const style = document.createElement('style');
            style.id = 'modal-animations';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideInScale {
                    from {
                        transform: scale(0.8) translateY(-20px);
                        opacity: 0;
                    }
                    to {
                        transform: scale(1) translateY(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Handle button clicks
        document.getElementById('confirm-cancel').onclick = function() {
            modalOverlay.style.animation = 'fadeIn 0.3s ease-out reverse';
            setTimeout(() => {
                modalOverlay.remove();
            }, 300);
        };

        document.getElementById('confirm-ok').onclick = function() {
            modalOverlay.style.animation = 'fadeIn 0.3s ease-out reverse';
            setTimeout(() => {
                modalOverlay.remove();
                onConfirm();
            }, 300);
        };

        // Handle escape key
        const handleEscape = function(e) {
            if (e.key === 'Escape') {
                document.getElementById('confirm-cancel').click();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);

        // Handle click outside modal
        modalOverlay.onclick = function(e) {
            if (e.target === modalOverlay) {
                document.getElementById('confirm-cancel').click();
            }
        };
    }

    // Function to show custom alert messages
    function showAlertMessage(type, title, message) {
        // Create alert container if it doesn't exist
        let alertContainer = document.getElementById('alert-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'alert-container';
            alertContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
            `;
            document.body.appendChild(alertContainer);
        }

        // Create alert element
        const alertElement = document.createElement('div');
        alertElement.style.cssText = `
            background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
            color: ${type === 'success' ? '#155724' : '#721c24'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        `;

        alertElement.innerHTML = `
            <div style="flex-shrink: 0;">
                <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}" 
                   style="font-size: 18px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 5px;">${title}</div>
                <div style="font-size: 14px; opacity: 0.9;">${message}</div>
            </div>
            <button onclick="this.parentElement.remove()" 
                    style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; margin-left: 10px;">
                <i class="fa-solid fa-times"></i>
            </button>
        `;

        // Add CSS animation
        if (!document.getElementById('alert-animations')) {
            const style = document.createElement('style');
            style.id = 'alert-animations';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        alertContainer.appendChild(alertElement);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertElement.parentElement) {
                alertElement.style.animation = 'slideInRight 0.3s ease-out reverse';
                setTimeout(() => {
                    alertElement.remove();
                }, 300);
            }
        }, 5000);
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
                } else {
                    alert('Error loading notification counts: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error loading notification counts: ' + error.message);
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
            if (data.success) {
                console.log('Section marked as visited:', data);
            } else {
                alert('Error marking section as visited: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error marking section as visited: ' + error.message);
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