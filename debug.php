<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Panel - Seminar KP</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        .section {
            background: #2a2a2a;
            border: 2px solid #00ff00;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .success {
            color: #00ff00;
        }
        .error {
            color: #ff0000;
        }
        .warning {
            color: #ffaa00;
        }
        h1, h2 {
            color: #00ffff;
        }
        button {
            background: #00ff00;
            color: #000;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
        }
        pre {
            background: #000;
            padding: 10px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔧 DEBUG PANEL - SISTEM SEMINAR KP</h1>
    
    <div class="section">
        <h2>1️⃣ PHP Status</h2>
        <div id="phpStatus">
            <?php
            echo "<p class='success'>✅ PHP BERFUNGSI (Version: " . phpversion() . ")</p>";
            ?>
        </div>
    </div>

    <div class="section">
        <h2>2️⃣ Database Connection</h2>
        <div id="dbStatus">
            <?php
            $host = 'localhost';
            $user = 'root';
            $password = '';
            $dbname = 'seminar_kp';
            
            $conn = new mysqli($host, $user, $password, $dbname);
            
            if ($conn->connect_error) {
                echo "<p class='error'>❌ DATABASE TIDAK TERKONEKSI</p>";
                echo "<p>Error: " . $conn->connect_error . "</p>";
                echo "<p class='warning'>⚠️ SOLUSI: Import file php/setup.sql di phpMyAdmin</p>";
            } else {
                echo "<p class='success'>✅ DATABASE TERKONEKSI</p>";
                
                // Check tables
                echo "<h3>Tables:</h3>";
                $tables = ['users', 'seminars', 'registrations'];
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($result->num_rows > 0) {
                        $count = $conn->query("SELECT COUNT(*) as total FROM $table")->fetch_assoc();
                        echo "<p class='success'>✅ Table '$table' ada ({$count['total']} records)</p>";
                    } else {
                        echo "<p class='error'>❌ Table '$table' TIDAK ADA!</p>";
                    }
                }
            }
            ?>
        </div>
    </div>

    <div class="section">
        <h2>3️⃣ Test Login API</h2>
        <p>Email: <input type="email" id="testEmail" value="test@example.com"></p>
        <p>Password: <input type="password" id="testPass" value="password123"></p>
        <button onclick="testLogin()">Test Login</button>
        <pre id="loginResult"></pre>
    </div>

    <div class="section">
        <h2>4️⃣ Test Register API</h2>
        <button onclick="testRegister()">Test Register New User</button>
        <pre id="registerResult"></pre>
    </div>

    <div class="section">
        <h2>5️⃣ Browser Info</h2>
        <pre id="browserInfo"></pre>
    </div>

    <script>
        // Show browser info
        document.getElementById('browserInfo').textContent = 
            'User Agent: ' + navigator.userAgent + '\n' +
            'LocalStorage Available: ' + (typeof(Storage) !== "undefined") + '\n' +
            'Current URL: ' + window.location.href;

        function testLogin() {
            const email = document.getElementById('testEmail').value;
            const pass = document.getElementById('testPass').value;
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', pass);

            fetch('php/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loginResult').textContent = 
                    'Response:\n' + JSON.stringify(data, null, 2);
            })
            .catch(err => {
                document.getElementById('loginResult').textContent = 
                    'ERROR: ' + err.message;
            });
        }

        function testRegister() {
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('name', 'Test User ' + Date.now());
            formData.append('email', 'test' + Date.now() + '@example.com');
            formData.append('password', 'password123');
            formData.append('nim', 'TEST' + Date.now());
            formData.append('angkatan', '2024');
            formData.append('jurusan', 'Informatika');

            fetch('php/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('registerResult').textContent = 
                    'Response:\n' + JSON.stringify(data, null, 2);
            })
            .catch(err => {
                document.getElementById('registerResult').textContent = 
                    'ERROR: ' + err.message;
            });
        }
    </script>
</body>
</html>
