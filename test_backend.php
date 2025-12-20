<?php
// TEST 1: Apakah PHP berjalan?
echo "✅ PHP BERJALAN<br>";
echo "PHP Version: " . phpversion() . "<br><br>";

// TEST 2: Apakah bisa koneksi database?
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'seminar_kp';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("❌ DATABASE GAGAL: " . $conn->connect_error);
}
echo "✅ DATABASE TERKONEKSI<br><br>";

// TEST 3: Debug POST data
echo "<h3>POST Data yang diterima:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// TEST 4: Simulasi registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'test_register') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        echo "<h3>Data yang akan disimpan:</h3>";
        echo "Nama: $name<br>";
        echo "Email: $email<br>";
        echo "Password (Original): $password<br>";
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        echo "Password (Hashed): $hashedPassword<br><br>";
        
        // Check email exists
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            echo "⚠️ Email sudah ada di database<br>";
        } else {
            // Try INSERT
            $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashedPassword')";
            if ($conn->query($sql) === TRUE) {
                echo "✅ BERHASIL INSERT KE DATABASE!<br>";
                echo "User ID: " . $conn->insert_id . "<br>";
            } else {
                echo "❌ INSERT GAGAL: " . $conn->error . "<br>";
            }
        }
    }
}

$conn->close();
?>

<hr>
<h2>Form Test Registrasi</h2>
<form method="POST">
    <input type="hidden" name="action" value="test_register">
    Nama: <input type="text" name="name" value="Test User" required><br><br>
    Email: <input type="email" name="email" value="test<?php echo time(); ?>@mail.com" required><br><br>
    Password: <input type="text" name="password" value="123" required><br><br>
    <button type="submit" style="background:#00ff00; padding:10px 20px; font-weight:bold; border:none; cursor:pointer;">
        COBA DAFTAR
    </button>
</form>

<hr>
<h3>Cek Tabel Users:</h3>
<?php
$conn = new mysqli($host, $user, $password, $dbname);
$result = $conn->query("SELECT id, name, email, created_at FROM users ORDER BY id DESC LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Nama</th><th>Email</th><th>Tanggal Daftar</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . ($row['created_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data user.";
}
$conn->close();
?>
