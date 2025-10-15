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
    
    // Get log statistics
    $stmt = $conn->query("SELECT COUNT(*) as total_logs FROM arduino_logs");
    $totalLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total_logs'];
    
    $stmt = $conn->query("SELECT COUNT(*) as error_logs FROM arduino_logs WHERE log_level = 'ERROR'");
    $errorLogs = $stmt->fetch(PDO::FETCH_ASSOC)['error_logs'];
    
    $stmt = $conn->query("SELECT COUNT(*) as warning_logs FROM arduino_logs WHERE log_level = 'WARNING'");
    $warningLogs = $stmt->fetch(PDO::FETCH_ASSOC)['warning_logs'];
    
    // Get recent logs (last 50)
    $stmt = $conn->query("
        SELECT id, machine_id, log_level, log_message, log_category, timestamp 
        FROM arduino_logs 
        ORDER BY timestamp DESC 
        LIMIT 50
    ");
    $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get log categories for filtering
    $stmt = $conn->query("SELECT DISTINCT log_category FROM arduino_logs WHERE log_category IS NOT NULL ORDER BY log_category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
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
    <title>Arduino Serial Monitor - Rice Vending Machine</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .logs-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 120px);
            gap: 20px;
        }
        
        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--bg-primary);
            border-radius: 15px;
            box-shadow: none;
            border: 1px solid var(--border-color);
        }
        
        .logs-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logs-title h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 24px;
        }
        
        .logs-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .log-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
            min-width: 120px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        .stat-total .stat-value { color: var(--accent-color); }
        .stat-error .stat-value { color: var(--danger-color); }
        .stat-warning .stat-value { color: var(--warning-color); }
        
        .logs-filters {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 15px;
            background: var(--bg-primary);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .logs-monitor {
            flex: 1;
            background: #1a1a1a;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .logs-monitor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        
        .monitor-title {
            color: #00ff00;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .monitor-controls {
            display: flex;
            gap: 10px;
        }
        
        .monitor-btn {
            background: #333;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }
        
        .monitor-btn:hover {
            background: #555;
        }
        
        .monitor-btn.active {
            background: var(--accent-color);
        }
        
        .logs-content {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }
        
        .log-entry {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #333;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-timestamp {
            color: #888;
            min-width: 80px;
            font-size: 11px;
        }
        
        .log-level {
            min-width: 60px;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .log-level.DEBUG { color: #888; background: #333; }
        .log-level.INFO { color: #00ff00; background: #003300; }
        .log-level.WARNING { color: #ffaa00; background: #332200; }
        .log-level.ERROR { color: #ff0000; background: #330000; }
        .log-level.SYSTEM { color: #00aaff; background: #002233; }
        
        .log-category {
            color: #ffaa00;
            min-width: 80px;
            font-size: 11px;
        }
        
        .log-message {
            color: #fff;
            flex: 1;
            word-break: break-word;
        }
        
        .auto-scroll-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 255, 0, 0.2);
            color: #00ff00;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            display: none;
        }
        
        .auto-scroll-indicator.active {
            display: block;
        }
        
        .no-logs {
            text-align: center;
            color: #888;
            padding: 40px;
            font-style: italic;
        }
        
        .loading {
            text-align: center;
            color: #00ff00;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .logs-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .logs-controls {
                justify-content: space-between;
            }
            
            .log-stats {
                flex-wrap: wrap;
            }
            
            .logs-filters {
                flex-wrap: wrap;
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
        <div class="logs-container">
            <div class="logs-header">
                <div class="logs-title">
                    <i class="fa-solid fa-terminal"></i>
                    <h1>Arduino Serial Monitor</h1>
                </div>
                <div class="logs-controls">
                    <button class="monitor-btn" id="refreshBtn">
                        <i class="fa-solid fa-refresh"></i> Refresh
                    </button>
                    <button class="monitor-btn" id="clearBtn">
                        <i class="fa-solid fa-trash"></i> Clear
                    </button>
                    <button class="monitor-btn active" id="autoScrollBtn">
                        <i class="fa-solid fa-arrow-down"></i> Auto Scroll
                    </button>
                </div>
            </div>
            
            <div class="log-stats">
                <div class="stat-card stat-total">
                    <div class="stat-value"><?php echo $totalLogs; ?></div>
                    <div class="stat-label">Total Logs</div>
                </div>
                <div class="stat-card stat-error">
                    <div class="stat-value"><?php echo $errorLogs; ?></div>
                    <div class="stat-label">Errors</div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-value"><?php echo $warningLogs; ?></div>
                    <div class="stat-label">Warnings</div>
                </div>
            </div>
            
            <div class="logs-filters">
                <div class="filter-group">
                    <label>Log Level</label>
                    <select id="logLevelFilter">
                        <option value="">All Levels</option>
                        <option value="DEBUG">Debug</option>
                        <option value="INFO">Info</option>
                        <option value="WARNING">Warning</option>
                        <option value="ERROR">Error</option>
                        <option value="SYSTEM">System</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Auto Refresh</label>
                    <select id="refreshInterval">
                        <option value="0">Off</option>
                        <option value="5000">5 seconds</option>
                        <option value="10000" selected>10 seconds</option>
                        <option value="30000">30 seconds</option>
                    </select>
                </div>
            </div>
            
            <div class="logs-monitor">
                <div class="logs-monitor-header">
                    <div class="monitor-title">
                        <i class="fa-solid fa-microchip"></i>
                        Rice Dispenser Serial Output
                    </div>
                    <div class="auto-scroll-indicator" id="autoScrollIndicator">
                        Auto-scrolling
                    </div>
                </div>
                <div class="logs-content" id="logsContent">
                    <div class="loading" id="loadingIndicator">
                        <i class="fa-solid fa-spinner fa-spin"></i> Loading logs...
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    let autoScroll = true;
    let refreshInterval = 10000; // 10 seconds default
    let refreshTimer = null;
    let currentFilters = {
        logLevel: '',
        category: ''
    };
    
    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        loadLogs();
        setupEventListeners();
        startAutoRefresh();
    });
    
    function setupEventListeners() {
        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            loadLogs();
        });
        
        // Clear button
        document.getElementById('clearBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                clearLogs();
            }
        });
        
        // Auto scroll toggle
        document.getElementById('autoScrollBtn').addEventListener('click', function() {
            autoScroll = !autoScroll;
            this.classList.toggle('active', autoScroll);
            document.getElementById('autoScrollIndicator').classList.toggle('active', autoScroll);
        });
        
        // Filter changes
        document.getElementById('logLevelFilter').addEventListener('change', function() {
            currentFilters.logLevel = this.value;
            loadLogs();
        });
        
        document.getElementById('categoryFilter').addEventListener('change', function() {
            currentFilters.category = this.value;
            loadLogs();
        });
        
        // Refresh interval change
        document.getElementById('refreshInterval').addEventListener('change', function() {
            refreshInterval = parseInt(this.value);
            startAutoRefresh();
        });
    }
    
    function startAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
        }
        
        if (refreshInterval > 0) {
            refreshTimer = setInterval(loadLogs, refreshInterval);
        }
    }
    
    function loadLogs() {
        const params = new URLSearchParams();
        if (currentFilters.logLevel) params.append('log_level', currentFilters.logLevel);
        if (currentFilters.category) params.append('category', currentFilters.category);
        params.append('limit', '100');
        
        fetch(`arduino_logs_api.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    displayLogs(data.data.logs);
                    updateStats(data.data.logs);
                } else {
                    console.error('Error loading logs:', data.message);
                    showError('Failed to load logs: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error while loading logs');
            });
    }
    
    function displayLogs(logs) {
        const logsContent = document.getElementById('logsContent');
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        if (logs.length === 0) {
            logsContent.innerHTML = '<div class="no-logs">No logs found matching the current filters.</div>';
            return;
        }
        
        let html = '';
        logs.forEach(log => {
            const timestamp = new Date(log.timestamp).toLocaleTimeString();
            const logLevel = log.log_level || 'INFO';
            const category = log.log_category || '';
            const message = escapeHtml(log.log_message);
            
            html += `
                <div class="log-entry">
                    <div class="log-timestamp">${timestamp}</div>
                    <div class="log-level ${logLevel}">${logLevel}</div>
                    <div class="log-category">${category}</div>
                    <div class="log-message">${message}</div>
                </div>
            `;
        });
        
        logsContent.innerHTML = html;
        
        // Auto scroll to bottom if enabled
        if (autoScroll) {
            logsContent.scrollTop = logsContent.scrollHeight;
        }
    }
    
    function updateStats(logs) {
        // Update stats based on current logs
        const totalLogs = logs.length;
        const errorLogs = logs.filter(log => log.log_level === 'ERROR').length;
        const warningLogs = logs.filter(log => log.log_level === 'WARNING').length;
        
        // Update the stat cards
        document.querySelector('.stat-total .stat-value').textContent = totalLogs;
        document.querySelector('.stat-error .stat-value').textContent = errorLogs;
        document.querySelector('.stat-warning .stat-value').textContent = warningLogs;
    }
    
    function clearLogs() {
        fetch('arduino_logs_api.php', {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                loadLogs();
            } else {
                alert('Failed to clear logs: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error while clearing logs');
        });
    }
    
    function showError(message) {
        const logsContent = document.getElementById('logsContent');
        logsContent.innerHTML = `<div class="no-logs" style="color: #ff0000;">${message}</div>`;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
