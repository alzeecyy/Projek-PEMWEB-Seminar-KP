<?php
// Test file untuk memastikan PHP dan Database berfungsi
echo "<h1 style='color:green;'>✅ PHP Berfungsi!</h1>";
echo "<p>Jika Anda melihat pesan ini, berarti Apache sudah jalan.</p>";
echo "<hr>";

require 'db.php';

if ($conn->connect_error) {
    die("<h2 style='color:red;'>❌ Database Connection Failed</h2><p>" . $conn->connect_error . "</p>");
}

echo "<h2 style='color:green;'>✅ Database Connected Successfully!</h2>";
echo "<p>Database: seminar_kp</p>";

// Check tables
$result = $conn->query("SHOW TABLES");
echo "<h3>Tables in database:</h3><ul>";
while($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Count users
$count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc();
echo "<p><strong>Total Users:</strong> " . $count['total'] . "</p>";

$conn->close();
?>
