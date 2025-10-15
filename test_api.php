<?php
// Test the API endpoint that the Arduino uses
echo "<h2>Testing get_rice_config.php API</h2>\n";

$url = "http://localhost/rice_dispenser_iot-master/get_rice_config.php";
$response = file_get_contents($url);

echo "<h3>Raw API Response:</h3>\n";
echo "<pre>" . htmlspecialchars($response) . "</pre>\n";

echo "<h3>Parsed JSON:</h3>\n";
$data = json_decode($response, true);
if ($data) {
    echo "<pre>" . print_r($data, true) . "</pre>\n";
    
    if (isset($data['data']['items'])) {
        echo "<h3>Rice Items Analysis:</h3>\n";
        foreach ($data['data']['items'] as $index => $item) {
            $itemNum = $index + 1;
            echo "<h4>Item {$itemNum}:</h4>\n";
            echo "<ul>\n";
            echo "<li><strong>Name:</strong> " . htmlspecialchars($item['name']) . "</li>\n";
            echo "<li><strong>Price:</strong> ₱" . number_format($item['price'], 2) . "</li>\n";
            echo "<li><strong>Capacity:</strong> " . $item['capacity'] . " kg</li>\n";
            echo "<li><strong>Expiration Date:</strong> " . ($item['expiration_date'] ?: 'Not set') . "</li>\n";
            
            // Check if expired
            if ($item['expiration_date']) {
                $expDate = new DateTime($item['expiration_date']);
                $today = new DateTime();
                if ($expDate < $today) {
                    echo "<li><strong>Status:</strong> <span style='color: red; font-weight: bold;'>EXPIRED</span></li>\n";
                } else {
                    $daysLeft = $today->diff($expDate)->days;
                    echo "<li><strong>Status:</strong> <span style='color: green;'>Good for {$daysLeft} days</span></li>\n";
                }
            } else {
                echo "<li><strong>Status:</strong> No expiration date</li>\n";
            }
            echo "</ul>\n";
        }
    }
} else {
    echo "<p style='color: red;'>Failed to parse JSON response</p>\n";
}
?>
