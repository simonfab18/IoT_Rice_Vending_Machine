<?php
require_once 'email_notifications.php';

echo "<h2>Final Email System Test</h2>";
echo "<p>Testing all email types with corrected templates...</p>";

// Test 1: Transaction Email
echo "<h3>1. Testing Transaction Email</h3>";
$transactionData = [
    'id' => 1001,
    'amount' => 180,
    'kilos' => 3.0,
    'rice_name' => 'Premium Jasmine Rice',
    'price_per_kg' => 60.00
];

$result1 = EmailNotifications::sendTransactionNotification($transactionData);
echo $result1 ? "✅ Transaction email sent!" : "❌ Transaction email failed";
echo "<br>";

// Test 2: Low Stock Email
echo "<h3>2. Testing Low Stock Email</h3>";
$lowStockData = [
    'name' => 'Test Rice',
    'stock' => 1.2,
    'capacity' => 10.0
];

$result2 = EmailNotifications::sendLowStockAlert($lowStockData);
echo $result2 ? "✅ Low stock email sent!" : "❌ Low stock email failed";
echo "<br>";

// Test 3: Expiration Email (Expired)
echo "<h3>3. Testing Expired Rice Email</h3>";
$expiredData = [
    'name' => 'Expired Test Rice',
    'manufacturer' => 'Test Farm',
    'expiration_date' => '2023-11-15',
    'stock' => 2.5
];

$result3 = EmailNotifications::sendExpirationAlert($expiredData, -10);
echo $result3 ? "✅ Expired rice email sent!" : "❌ Expired rice email failed";
echo "<br>";

// Test 4: Expiration Email (Urgent)
echo "<h3>4. Testing Urgent Expiration Email</h3>";
$urgentData = [
    'name' => 'Urgent Test Rice',
    'manufacturer' => 'Test Farm',
    'expiration_date' => date('Y-m-d', strtotime('+3 days')),
    'stock' => 4.0
];

$result4 = EmailNotifications::sendExpirationAlert($urgentData, 3);
echo $result4 ? "✅ Urgent expiration email sent!" : "❌ Urgent expiration email failed";
echo "<br>";

// Test 5: Expiration Email (Warning)
echo "<h3>5. Testing Warning Expiration Email</h3>";
$warningData = [
    'name' => 'Warning Test Rice',
    'manufacturer' => 'Test Farm',
    'expiration_date' => date('Y-m-d', strtotime('+20 days')),
    'stock' => 6.0
];

$result5 = EmailNotifications::sendExpirationAlert($warningData, 20);
echo $result5 ? "✅ Warning expiration email sent!" : "❌ Warning expiration email failed";
echo "<br>";

echo "<hr>";
echo "<h3>Test Summary</h3>";
$totalTests = 5;
$passedTests = ($result1 ? 1 : 0) + ($result2 ? 1 : 0) + ($result3 ? 1 : 0) + ($result4 ? 1 : 0) + ($result5 ? 1 : 0);

echo "<p><strong>Results: $passedTests/$totalTests tests passed</strong></p>";

if ($passedTests == $totalTests) {
    echo "<p style='color: green; font-size: 18px;'>🎉 All email tests passed! Check your inbox.</p>";
} else {
    echo "<p style='color: orange; font-size: 18px;'>⚠️ Some tests failed. Check error logs.</p>";
}

echo "<hr>";
echo "<h3>What to Check</h3>";
echo "<ol>";
echo "<li><strong>Check your email inbox</strong> at farmartricestore@gmail.com</li>";
echo "<li><strong>Look for these email subjects:</strong>";
echo "<ul>";
echo "<li>New Transaction - Rice Dispenser</li>";
echo "<li>⚠️ Low Stock Alert - Test Rice</li>";
echo "<li>EXPIRED - Rice Expiration Alert - Expired Test Rice</li>";
echo "<li>URGENT - Rice Expiration Alert - Urgent Test Rice</li>";
echo "<li>WARNING - Rice Expiration Alert - Warning Test Rice</li>";
echo "</ul></li>";
echo "<li><strong>If you don't receive emails:</strong>";
echo "<ul>";
echo "<li>Check spam/junk folder</li>";
echo "<li>Check XAMPP error logs</li>";
echo "<li>Verify Gmail app password is correct</li>";
echo "<li>Test with a real transaction from Arduino</li>";
echo "</ul></li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Test with a real transaction from your Arduino</li>";
echo "<li>Add some rice with expiration dates to test expiration alerts</li>";
echo "<li>Lower rice stock to trigger low stock alerts</li>";
echo "</ol>";
?>
