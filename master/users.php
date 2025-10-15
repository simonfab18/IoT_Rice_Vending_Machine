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
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $status = 'active';
                
                if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
                    $error = 'All fields are required';
                } elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters long';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email format';
                } else {
                    // Check if username or email already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($exists > 0) {
                        $error = 'Username or email already exists';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $full_name = $first_name . ' ' . $last_name; // Combine for backward compatibility
                        $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password, full_name, first_name, last_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$username, $email, $hashedPassword, $full_name, $first_name, $last_name, $status]);
                        $message = 'User added successfully';
                    }
                }
                break;
                
            case 'edit_user':
                $user_id = $_POST['user_id'] ?? '';
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $status = 'active';
                
                if (empty($user_id) || empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
                    $error = 'All fields are required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email format';
                } else {
                    // Check if username or email already exists (excluding current user)
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE (username = ? OR email = ?) AND id != ?");
                    $stmt->execute([$username, $email, $user_id]);
                    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($exists > 0) {
                        $error = 'Username or email already exists';
                    } else {
                        $full_name = $first_name . ' ' . $last_name; // Combine for backward compatibility
                        $stmt = $conn->prepare("UPDATE admin_users SET username = ?, email = ?, full_name = ?, first_name = ?, last_name = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$username, $email, $full_name, $first_name, $last_name, $status, $user_id]);
                        $message = 'User updated successfully';
                    }
                }
                break;
                
            case 'change_password':
                $user_id = $_POST['user_id'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($user_id) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All fields are required';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters long';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Passwords do not match';
                } else {
                    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashedPassword, $user_id]);
                    $message = 'Password changed successfully';
                }
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'] ?? '';
                
                if (empty($user_id)) {
                    $error = 'User ID is required';
                } elseif ($user_id == $_SESSION['admin_id']) {
                    $error = 'You cannot delete your own account';
                } else {
                    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $message = 'User deleted successfully';
                }
                break;
        }
    }
    
    // Get all users
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $stmt = $conn->prepare("SELECT * FROM admin_users $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user statistics
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users
        FROM admin_users");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Rice Vending Machine</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .users-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border-radius: 10px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #4CAF50;
        }
        
        .stat-card.inactive {
            border-left-color: #ff9800;
        }
        
        .stat-card.suspended {
            border-left-color: #f44336;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .controls-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .controls-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 25px;
            background: white;
            font-size: 14px;
            min-width: 150px;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .users-table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .table-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }
        
        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-suspended {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit:hover {
            background: #138496;
        }
        
        .btn-password {
            background: #ffc107;
            color: #333;
        }
        
        .btn-password:hover {
            background: #e0a800;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .last-login {
            font-size: 12px;
            color: #666;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
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
            position: relative;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .close {
            color: white;
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
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
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
        
        @media (max-width: 768px) {
            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .users-table {
                font-size: 12px;
            }
            
            .users-table th,
            .users-table td {
                padding: 10px 8px;
            }
            
            .action-buttons {
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
        });
    </script>
    
    <main class="main-content">
        <div class="users-container">
            <div class="page-header">
                <div>
                    <h1 class="page-title"><i class="fa-solid fa-users"></i> User Management</h1>
                    <p>Manage admin users and their permissions</p>
                </div>
                <button class="btn-primary" onclick="openAddUserModal()">
                    <i class="fa-solid fa-plus"></i> Add New User
                </button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card inactive">
                    <div class="stat-number"><?php echo $stats['inactive_users']; ?></div>
                    <div class="stat-label">Inactive Users</div>
                </div>
            </div>
            
            <!-- Controls Section -->
            <div class="controls-section">
                <form method="GET" class="controls-row">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fa-solid fa-search"></i>
                    </div>
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="users.php" class="btn-primary" style="background: var(--text-muted);">
                            <i class="fa-solid fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="users-table-container">
                <div class="table-header">
                    <h2 class="table-title">Admin Users</h2>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="no-data">
                        <i class="fa-solid fa-users"></i>
                        <h3>No users found</h3>
                        <p>No users match your search criteria.</p>
                    </div>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                            <span style="color: #4CAF50; font-size: 12px;">(You)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['first_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($user['last_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <div class="last-login">
                                                <?php echo date('M d, Y', strtotime($user['last_login'])); ?><br>
                                                <?php echo date('H:i', strtotime($user['last_login'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #999;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="last-login">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?><br>
                                            <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-sm btn-edit" onclick="openEditUserModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fa-solid fa-edit"></i> Edit
                                            </button>
                                            <button class="btn-sm btn-password" onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fa-solid fa-key"></i> Password
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                                <button class="btn-sm btn-delete" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('addUserModal')">&times;</span>
                <h2><i class="fa-solid fa-user-plus"></i> Add New User</h2>
            </div>
            <div class="modal-body">
                <form method="POST" id="addUserForm">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-group">
                        <label for="add_username">Username *</label>
                        <input type="text" id="add_username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_email">Email *</label>
                        <input type="email" id="add_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_first_name">First Name *</label>
                        <input type="text" id="add_first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_last_name">Last Name *</label>
                        <input type="text" id="add_last_name" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_password">Password *</label>
                        <input type="password" id="add_password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="add_status">Status</label>
                        <select id="add_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('editUserModal')">&times;</span>
                <h2><i class="fa-solid fa-user-edit"></i> Edit User</h2>
            </div>
            <div class="modal-body">
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="form-group">
                        <label for="edit_username">Username *</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email *</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_first_name">First Name *</label>
                        <input type="text" id="edit_first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_last_name">Last Name *</label>
                        <input type="text" id="edit_last_name" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('passwordModal')">&times;</span>
                <h2><i class="fa-solid fa-key"></i> Change Password</h2>
            </div>
            <div class="modal-body">
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="password_user_id">
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('passwordModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--danger-color), #c82333);">
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                <h2><i class="fa-solid fa-exclamation-triangle"></i> Confirm Delete</h2>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user <strong id="delete_username"></strong>?</p>
                <p style="color: #dc3545; font-weight: 600;">This action cannot be undone!</p>
                
                <form method="POST" id="deleteForm" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                        <button type="submit" class="btn-primary" style="background: var(--danger-color);">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function openEditUserModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_first_name').value = user.first_name || '';
            document.getElementById('edit_last_name').value = user.last_name || '';
            document.getElementById('edit_status').value = user.status;
            
            document.getElementById('editUserModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function openPasswordModal(userId, username) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('passwordModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function confirmDeleteUser(userId, username) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_username').textContent = username;
            document.getElementById('deleteModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Clear forms
            if (modalId === 'addUserModal') {
                document.getElementById('addUserForm').reset();
            } else if (modalId === 'editUserModal') {
                document.getElementById('editUserForm').reset();
            } else if (modalId === 'passwordModal') {
                document.getElementById('passwordForm').reset();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = ['addUserModal', 'editUserModal', 'passwordModal', 'deleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    closeModal(modalId);
                }
            });
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = ['addUserModal', 'editUserModal', 'passwordModal', 'deleteModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal.style.display === 'block') {
                        closeModal(modalId);
                    }
                });
            }
        });
        
        // Password confirmation validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
        
        // Auto-hide alerts after 5 seconds
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
    </script>
</body>
</html>
