<?php
// Simulate a transaction from Arduino to test email notifications
echo "<h2>Simulating Arduino Transaction</h2>";

// Simulate POST data that would come from Arduino
$_POST['totalAmount'] = 150;
$_POST['riceName'] = 'Jasmine Rice';
$_POST['quantity'] = 2.5;
$_POST['pricePerKg'] = 60.00;

echo "<h3>Simulated Arduino Data:</h3>";
echo "Total Amount: ₱" . $_POST['totalAmount'] . "<br>";
echo "Rice Name: " . $_POST['riceName'] . "<br>";
echo "Quantity: " . $_POST['quantity'] . " kg<br>";
echo "Price per kg: ₱" . $_POST['pricePerKg'] . "<br>";

echo "<h3>Processing Transaction...</h3>";

// Capture the output from upload.php
ob_start();
include 'upload.php';
$output = ob_get_clean();

echo "<h3>Upload.php Response:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Parse the JSON response
$response = json_decode($output, true);

if ($response && isset($response['status'])) {
    if ($response['status'] === 'success') {
        echo "<p style='color: green; font-size: 18px;'>✅ Transaction processed successfully!</p>";
        echo "<p>Transaction ID: " . $response['data']['id'] . "</p>";
        echo "<p><strong>Check your email for the transaction notification!</strong></p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Transaction failed: " . $response['message'] . "</p>";
    }
} else {
    echo "<p style='color: orange; font-size: 18px;'>⚠️ Unexpected response format</p>";
}

echo "<hr>";

// Test expiration alerts
echo "<h3>Testing Expiration Alerts...</h3>";
echo "<p>Running expiration check...</p>";

ob_start();
include 'check_expiration_alerts.php';
$expirationOutput = ob_get_clean();

echo "<h3>Expiration Check Response:</h3>";
echo "<pre>" . htmlspecialchars($expirationOutput) . "</pre>";

$expirationResponse = json_decode($expirationOutput, true);

if ($expirationResponse && isset($expirationResponse['success'])) {
    if ($expirationResponse['success']) {
        echo "<p style='color: green;'>✅ Expiration check completed!</p>";
        echo "<p>Alerts created: " . $expirationResponse['alerts_created'] . "</p>";
        echo "<p><strong>Check your email for any expiration notifications!</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Expiration check failed: " . $expirationResponse['error'] . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Unexpected expiration response format</p>";
}

echo "<hr>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>If transaction email was sent, the system is working for new transactions</li>";
echo "<li>If expiration emails were sent, the system is working for expiration alerts</li>";
echo "<li>If low stock emails work but these don't, there might be an issue with the specific functions</li>";
echo "</ul>";

echo "<p><strong>Check your email inbox at farmartricestore@gmail.com</strong></p>";
?>
