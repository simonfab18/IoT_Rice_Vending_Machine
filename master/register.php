<?php
session_start();
require_once 'database.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: main.php');
    exit();
}


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists';
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists';
                } else {
                    // Create new admin user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password, full_name, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                        $success = 'Admin account created successfully! You can now login.';
                        // Clear form data
                        $_POST = [];
                    } else {
                        $error = 'Failed to create admin account';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Farmart Rice Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .register-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .register-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            padding-left: 45px;
        }

        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            font-size: 14px;
        }

        .success-message {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
            font-size: 14px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #45a049;
            text-decoration: underline;
        }

        .back-to-site {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.3s ease;
        }

        .back-to-site:hover {
            opacity: 0.8;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .password-strength.weak {
            color: #f44336;
        }

        .password-strength.medium {
            color: #ff9800;
        }

        .password-strength.strong {
            color: #4caf50;
        }

        @media (max-width: 480px) {
            .register-container {
                margin: 10px;
            }
            
            .register-header {
                padding: 30px 20px;
            }
            
            .register-form {
                padding: 30px 20px;
            }
            
            .register-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-site">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Site
    </a>

    <div class="register-container">
        <div class="register-header">
            <h1>
                <i class="fa-solid fa-user-plus"></i>
                Create Admin Account
            </h1>
            <p>Register as an administrator for Farmart Rice Store</p>
        </div>

        <div class="register-form">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <i class="fa-solid fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-id-card"></i>
                        <input type="text" id="full_name" name="full_name" required 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                               placeholder="Enter your full name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               placeholder="Choose a username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               placeholder="Enter your email address">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Create a password" minlength="6">
                    </div>
                    <div class="password-strength" id="password-strength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm your password">
                    </div>
                </div>

                <button type="submit" class="register-btn">
                    <i class="fa-solid fa-user-plus"></i>
                    Create Admin Account
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 6) strength++;
            else feedback.push('At least 6 characters');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('lowercase letter');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('uppercase letter');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('number');
            
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('special character');
            
            if (strength <= 2) {
                strengthDiv.textContent = 'Weak password. Add: ' + feedback.slice(0, 2).join(', ');
                strengthDiv.className = 'password-strength weak';
            } else if (strength <= 3) {
                strengthDiv.textContent = 'Medium strength password';
                strengthDiv.className = 'password-strength medium';
            } else {
                strengthDiv.textContent = 'Strong password!';
                strengthDiv.className = 'password-strength strong';
            }
        });

        // Password confirmation checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#f44336';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
        });

        // Add interactive effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
