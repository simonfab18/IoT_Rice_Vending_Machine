<?php
require_once 'email_notifications.php';

echo "<h2>Testing Specific Email Types</h2>";

// Test 1: Transaction Email
echo "<h3>Test 1: Transaction Email</h3>";
$transactionData = [
    'id' => 999,
    'amount' => 120,
    'kilos' => 2.0,
    'rice_name' => 'Jasmine Rice',
    'price_per_kg' => 60.00
];

echo "Testing transaction notification...<br>";
$transactionResult = EmailNotifications::sendTransactionNotification($transactionData);

if ($transactionResult) {
    echo "<p style='color: green;'>✅ Transaction email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Transaction email failed</p>";
}

echo "<hr>";

// Test 2: Expiration Email (Expired)
echo "<h3>Test 2: Expiration Email (Expired)</h3>";
$expiredRiceData = [
    'name' => 'Test Expired Rice',
    'manufacturer' => 'Test Manufacturer',
    'expiration_date' => '2023-12-01', // Past date
    'stock' => 5.0
];

echo "Testing expired rice notification...<br>";
$expiredResult = EmailNotifications::sendExpirationAlert($expiredRiceData, -5); // Negative days = expired

if ($expiredResult) {
    echo "<p style='color: green;'>✅ Expired rice email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Expired rice email failed</p>";
}

echo "<hr>";

// Test 3: Expiration Email (Urgent - 3 days left)
echo "<h3>Test 3: Expiration Email (Urgent - 3 days left)</h3>";
$urgentRiceData = [
    'name' => 'Test Urgent Rice',
    'manufacturer' => 'Test Manufacturer',
    'expiration_date' => date('Y-m-d', strtotime('+3 days')),
    'stock' => 3.0
];

echo "Testing urgent expiration notification...<br>";
$urgentResult = EmailNotifications::sendExpirationAlert($urgentRiceData, 3);

if ($urgentResult) {
    echo "<p style='color: green;'>✅ Urgent expiration email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Urgent expiration email failed</p>";
}

echo "<hr>";

// Test 4: Expiration Email (Warning - 15 days left)
echo "<h3>Test 4: Expiration Email (Warning - 15 days left)</h3>";
$warningRiceData = [
    'name' => 'Test Warning Rice',
    'manufacturer' => 'Test Manufacturer',
    'expiration_date' => date('Y-m-d', strtotime('+15 days')),
    'stock' => 7.0
];

echo "Testing warning expiration notification...<br>";
$warningResult = EmailNotifications::sendExpirationAlert($warningRiceData, 15);

if ($warningResult) {
    echo "<p style='color: green;'>✅ Warning expiration email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Warning expiration email failed</p>";
}

echo "<hr>";

// Test 5: Low Stock Email (for comparison)
echo "<h3>Test 5: Low Stock Email (for comparison)</h3>";
$lowStockData = [
    'name' => 'Test Low Stock Rice',
    'stock' => 1.5,
    'capacity' => 10.0
];

echo "Testing low stock notification...<br>";
$lowStockResult = EmailNotifications::sendLowStockAlert($lowStockData);

if ($lowStockResult) {
    echo "<p style='color: green;'>✅ Low stock email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Low stock email failed</p>";
}

echo "<hr>";

// Summary
echo "<h3>Test Summary</h3>";
echo "<p><strong>Check your email inbox at farmartricestore@gmail.com for:</strong></p>";
echo "<ul>";
echo "<li>New Transaction - Rice Dispenser</li>";
echo "<li>EXPIRED - Rice Expiration Alert</li>";
echo "<li>URGENT - Rice Expiration Alert</li>";
echo "<li>WARNING - Rice Expiration Alert</li>";
echo "<li>Low Stock Alert</li>";
echo "</ul>";

echo "<p><strong>If you received low stock emails but not transaction/expiration emails:</strong></p>";
echo "<ol>";
echo "<li>The email system is working (since low stock works)</li>";
echo "<li>The issue is likely in the specific email functions</li>";
echo "<li>Check the error logs for specific error messages</li>";
echo "<li>Make sure the email templates are being generated correctly</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check your email inbox</li>";
echo "<li>If some emails work but others don't, check the error logs</li>";
echo "<li>Test with a real transaction from the Arduino</li>";
echo "<li>Test with real expiration dates in your inventory</li>";
echo "</ol>";
?>
