<?php
// Simple test to check if archive_transaction.php is working
echo "<h2>Simple Archive Test</h2>";

// Test 1: Check if file exists
echo "<h3>1. File Check</h3>";
if (file_exists('archive_transaction.php')) {
    echo "✅ archive_transaction.php exists<br>";
} else {
    echo "❌ archive_transaction.php NOT found<br>";
    exit;
}

// Test 2: Check file content
echo "<h3>2. File Content Check</h3>";
$content = file_get_contents('archive_transaction.php');
if (strpos($content, 'archive_transaction.php') !== false) {
    echo "✅ File contains expected content<br>";
} else {
    echo "❌ File content seems incorrect<br>";
}

// Test 3: Test database connection
echo "<h3>3. Database Connection Test</h3>";
try {
    require_once 'database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if is_archived column exists
    $stmt = $conn->query("SHOW COLUMNS FROM transactions LIKE 'is_archived'");
    if ($stmt->fetch()) {
        echo "✅ is_archived column exists<br>";
    } else {
        echo "❌ is_archived column does NOT exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Simulate the archive request
echo "<h3>4. Simulate Archive Request</h3>";
echo "<button onclick='testArchive()'>Test Archive Function</button>";
echo "<div id='result'></div>";

echo "<script>
function testArchive() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = 'Testing...';
    
    // Test with a real transaction ID
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
        resultDiv.innerHTML = 'Status: ' + response.status + '<br>';
        return response.text();
    })
    .then(data => {
        resultDiv.innerHTML += 'Response: ' + data;
    })
    .catch(error => {
        resultDiv.innerHTML = 'Error: ' + error.message;
    });
}
</script>";

echo "<h3>5. Manual Test</h3>";
echo "You can also test manually by running this command in your browser's console:<br>";
echo "<pre>";
echo "fetch('archive_transaction.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ transaction_id: 1, action: 'archive' })
}).then(r => r.text()).then(console.log)";
echo "</pre>";
?>
