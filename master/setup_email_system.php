<?php
require_once 'email_notifications.php';

echo "<h1>Rice Dispenser Email System Setup</h1>";

echo "<h2>Email Configuration</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>SMTP Host</td><td>" . SMTP_HOST . "</td></tr>";
echo "<tr><td>SMTP Port</td><td>" . SMTP_PORT . "</td></tr>";
echo "<tr><td>SMTP Username</td><td>" . SMTP_USERNAME . "</td></tr>";
echo "<tr><td>SMTP Password</td><td>" . str_repeat('*', strlen(SMTP_PASSWORD)) . "</td></tr>";
echo "<tr><td>From Email</td><td>" . FROM_EMAIL . "</td></tr>";
echo "<tr><td>Admin Email</td><td>" . ADMIN_EMAIL . "</td></tr>";
echo "</table>";

echo "<h2>System Status</h2>";

// Check if mail function is available
if (function_exists('mail')) {
    echo "<p style='color: green;'>✅ PHP mail() function is available</p>";
} else {
    echo "<p style='color: red;'>❌ PHP mail() function is not available</p>";
}

// Check if required files exist
$requiredFiles = ['email_config.php', 'email_notifications.php', 'phpmailer_email.php'];
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file is missing</p>";
    }
}

echo "<h2>Test Email System</h2>";
echo "<form method='post'>";
echo "<input type='submit' name='test_email' value='Send Test Email' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
echo "</form>";

if (isset($_POST['test_email'])) {
    echo "<h3>Testing Email System...</h3>";
    
    $result = EmailNotifications::sendTestEmail();
    
    if ($result) {
        echo "<p style='color: green; font-size: 18px;'>✅ Test email sent successfully!</p>";
        echo "<p>Check your email inbox at <strong>" . ADMIN_EMAIL . "</strong> for the test email.</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Failed to send test email</p>";
        echo "<p>Please check the following:</p>";
        echo "<ul>";
        echo "<li>Gmail app password is correct</li>";
        echo "<li>Server has internet connection</li>";
        echo "<li>PHP mail() function is enabled</li>";
        echo "<li>Check server error logs for any issues</li>";
        echo "</ul>";
    }
}

echo "<h2>Email Notification Types</h2>";
echo "<p>The system will send email notifications for:</p>";
echo "<ul>";
echo "<li><strong>New Transactions:</strong> Every time a customer makes a purchase</li>";
echo "<li><strong>Low Stock Alerts:</strong> When rice inventory falls below 2kg</li>";
echo "<li><strong>Expiration Alerts:</strong> When rice is expiring (30 days, 7 days, or expired)</li>";
echo "</ul>";

echo "<h2>Integration Status</h2>";
$integratedFiles = ['upload.php', 'check_expiration_alerts.php', 'inventory.php', 'main.php'];
foreach ($integratedFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'email_notifications.php') !== false) {
            echo "<p style='color: green;'>✅ $file - Email notifications integrated</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $file - Email notifications not integrated</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $file - File not found</p>";
    }
}

echo "<hr>";
echo "<p><strong>Setup Complete!</strong> The email notification system is now ready to use.</p>";
echo "<p>To test the system, click the 'Send Test Email' button above.</p>";
?>
