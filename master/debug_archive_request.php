<?php
echo "<h2>Archive Request Debug</h2>";

echo "<h3>1. Check if archive_transaction.php exists and is accessible</h3>";
$archiveFile = 'archive_transaction.php';
if (file_exists($archiveFile)) {
    echo "✅ archive_transaction.php file exists<br>";
    echo "File size: " . filesize($archiveFile) . " bytes<br>";
    echo "File permissions: " . substr(sprintf('%o', fileperms($archiveFile)), -4) . "<br>";
} else {
    echo "❌ archive_transaction.php file does NOT exist<br>";
    exit;
}

echo "<h3>2. Test direct access to archive_transaction.php</h3>";
echo "Testing GET request (should return error):<br>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/archive_transaction.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($error) {
    echo "❌ cURL Error: $error<br>";
} else {
    echo "✅ Response: $response<br>";
}

echo "<h3>3. Test POST request to archive_transaction.php</h3>";
$testData = [
    'transaction_id' => 1,
    'action' => 'archive'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/archive_transaction.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($error) {
    echo "❌ cURL Error: $error<br>";
} else {
    echo "✅ Response: $response<br>";
}

echo "<h3>4. Check JavaScript console for errors</h3>";
echo "Open your browser's Developer Tools (F12) and check the Console tab for any JavaScript errors.<br>";
echo "Also check the Network tab to see if the request to archive_transaction.php is being made.<br>";

echo "<h3>5. Test with a simple JavaScript request</h3>";
echo "<button onclick='testArchiveRequest()'>Test Archive Request</button>";
echo "<div id='testResult'></div>";

echo "<script>
function testArchiveRequest() {
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = 'Testing...';
    
    fetch('archive_transaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            transaction_id: 1,
            action: 'archive'
        })
    })
    .then(response => {
        resultDiv.innerHTML += '<br>Response status: ' + response.status;
        return response.text();
    })
    .then(data => {
        resultDiv.innerHTML += '<br>Response data: ' + data;
    })
    .catch(error => {
        resultDiv.innerHTML += '<br>❌ Error: ' + error.message;
    });
}
</script>";

echo "<h3>6. Common Issues and Solutions</h3>";
echo "<ul>";
echo "<li><strong>File Path:</strong> Make sure archive_transaction.php is in the same directory as transaction.php</li>";
echo "<li><strong>Server Configuration:</strong> Check if your server supports PHP and is running</li>";
echo "<li><strong>File Permissions:</strong> Ensure the web server can read the PHP file</li>";
echo "<li><strong>CORS Issues:</strong> If using different domains, check CORS settings</li>";
echo "<li><strong>JavaScript Errors:</strong> Check browser console for JavaScript errors</li>";
echo "</ul>";
?>
