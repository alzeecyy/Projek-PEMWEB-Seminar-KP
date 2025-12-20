<?php
session_start();
require 'db.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Email dan password harus diisi']);
            exit;
        }

        $sql = "SELECT id, name, role, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $email;
                
                $redirect = ($user['role'] === 'admin') ? 'admin.html' : 'home.html';
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Login berhasil', 
                    'redirect' => $redirect,
                    'name' => $user['name'],
                    'email' => $email,
                    'role' => $user['role']
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password salah']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email tidak ditemukan']);
        }
        $stmt->close();
    } 
    
    elseif ($action === 'register') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $nim = $_POST['nim'] ?? null;
        $angkatan = $_POST['angkatan'] ?? null;
        $jurusan = $_POST['jurusan'] ?? null;

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama, Email, dan Password harus diisi']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar']);
            exit;
        }

        $sql = "INSERT INTO users (name, email, password, nim, angkatan, jurusan) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $nim, $angkatan, $jurusan);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Registrasi berhasil', 'redirect' => 'login.html']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar: ' . $conn->error]);
        }
        $stmt->close();
    }
}
$conn->close();
