<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get current user info
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_profile':
                $full_name = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                
                if (empty($full_name) || empty($email)) {
                    $error = 'Full name and email are required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email format';
                } else {
                    // Check if email already exists for another user
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $_SESSION['admin_id']]);
                    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($exists > 0) {
                        $error = 'Email already exists for another user';
                    } else {
                        $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$full_name, $email, $_SESSION['admin_id']]);
                        $message = 'Profile updated successfully';
                        
                        // Update session
                        $_SESSION['admin_email'] = $email;
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All password fields are required';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                } elseif (!password_verify($current_password, $currentUser['password'])) {
                    $error = 'Current password is incorrect';
                } else {
                    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                    $message = 'Password changed successfully';
                }
                break;
        }
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Rice Vending Machine</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #e9ecef;
            --text-primary: #333333;
            --text-secondary: #666666;
            --text-muted: #999999;
            --border-color: #dee2e6;
            --accent-color: #4CAF50;
            --accent-hover: #45a049;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 15px rgba(0,0,0,0.15);
        }


        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            color: white;
            border-radius: 15px;
            box-shadow: none;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        
        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .settings-card {
            background: var(--bg-primary);
            border-radius: 15px;
            padding: 30px;
            box-shadow: none;
            border: 1px solid var(--border-color);
        }

        .settings-card:hover {
            box-shadow: none;
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .card-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            margin: 0;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .profile-info {
            background: var(--bg-primary);
            border-radius: 15px;
            padding: 30px;
            box-shadow: none;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }

        .profile-details h2 {
            margin: 0 0 5px 0;
            color: var(--text-primary);
            font-size: 24px;
        }

        .profile-details p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: var(--bg-secondary);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
        <div class="profile-container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">
                        <i class="fa-solid fa-user-cog"></i>
                        Profile Settings
                    </h1>
                    <p>Manage your account and preferences</p>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Information -->
            <div class="profile-info">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-details">
                        <h2><?php echo htmlspecialchars($currentUser['full_name']); ?></h2>
                        <p><?php echo htmlspecialchars($currentUser['email']); ?></p>
                        <p>Member since <?php echo date('M Y', strtotime($currentUser['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo date('d'); ?></div>
                        <div class="stat-label">Days Active</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $currentUser['status']; ?></div>
                        <div class="stat-label">Status</div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Grid -->
            <div class="settings-grid">
                <!-- Profile Information -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h3 class="card-title">Profile Information</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-input" 
                                   value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                            <small style="color: var(--text-muted);">Username cannot be changed</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i>
                            Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Security Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fa-solid fa-shield-alt"></i>
                        </div>
                        <h3 class="card-title">Security</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" minlength="6" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-input" minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-key"></i>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Theme toggle functionality
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('input[required], select[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = 'var(--danger-color)';
                    } else {
                        field.style.borderColor = 'var(--border-color)';
                    }
                });
                
                // Password confirmation validation
                const newPassword = form.querySelector('input[name="new_password"]');
                const confirmPassword = form.querySelector('input[name="confirm_password"]');
                
                if (newPassword && confirmPassword) {
                    if (newPassword.value !== confirmPassword.value) {
                        isValid = false;
                        confirmPassword.style.borderColor = 'var(--danger-color)';
                        alert('Passwords do not match!');
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
        
        // Real-time validation
        document.querySelectorAll('input[type="email"]').forEach(field => {
            field.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.style.borderColor = 'var(--danger-color)';
                } else {
                    this.style.borderColor = 'var(--border-color)';
                }
            });
        });
    </script>
</body>
</html>