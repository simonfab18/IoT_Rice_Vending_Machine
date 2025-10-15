<?php
require_once 'email_notifications.php';

echo "<h2>Testing Email Notification System</h2>";

// Test 1: Send test email
echo "<h3>Test 1: Sending Test Email</h3>";
$testResult = EmailNotifications::sendTestEmail();
if ($testResult) {
    echo "<p style='color: green;'>✅ Test email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send test email</p>";
}

// Test 2: Transaction notification
echo "<h3>Test 2: Transaction Notification</h3>";
$transactionData = [
    'id' => 999,
    'amount' => 150,
    'kilos' => 2.5,
    'rice_name' => 'Jasmine Rice',
    'price_per_kg' => 60.00
];

$transactionResult = EmailNotifications::sendTransactionNotification($transactionData);
if ($transactionResult) {
    echo "<p style='color: green;'>✅ Transaction notification sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send transaction notification</p>";
}

// Test 3: Low stock alert
echo "<h3>Test 3: Low Stock Alert</h3>";
$riceData = [
    'name' => 'Premium Rice',
    'stock' => 1.5,
    'capacity' => 10.0
];

$lowStockResult = EmailNotifications::sendLowStockAlert($riceData);
if ($lowStockResult) {
    echo "<p style='color: green;'>✅ Low stock alert sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send low stock alert</p>";
}

// Test 4: Expiration alert
echo "<h3>Test 4: Expiration Alert</h3>";
$expirationData = [
    'name' => 'Organic Rice',
    'manufacturer' => 'Farm Fresh',
    'expiration_date' => '2024-01-15',
    'stock' => 5.0
];

$expirationResult = EmailNotifications::sendExpirationAlert($expirationData, 3);
if ($expirationResult) {
    echo "<p style='color: green;'>✅ Expiration alert sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send expiration alert</p>";
}

echo "<hr>";
echo "<h3>Email Configuration</h3>";
echo "<p><strong>SMTP Host:</strong> " . SMTP_HOST . "</p>";
echo "<p><strong>SMTP Port:</strong> " . SMTP_PORT . "</p>";
echo "<p><strong>From Email:</strong> " . FROM_EMAIL . "</p>";
echo "<p><strong>Admin Email:</strong> " . ADMIN_EMAIL . "</p>";

echo "<hr>";
echo "<p><strong>Note:</strong> Check your email inbox at " . ADMIN_EMAIL . " for the test emails.</p>";
echo "<p>If emails are not received, check:</p>";
echo "<ul>";
echo "<li>Gmail app password is correct</li>";
echo "<li>Server has internet connection</li>";
echo "<li>PHP mail() function is enabled</li>";
echo "<li>Check server error logs for any issues</li>";
echo "</ul>";
?>
