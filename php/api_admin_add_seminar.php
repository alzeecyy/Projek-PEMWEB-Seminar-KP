<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

// Check Admin Access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? '';
    $speaker = $_POST['speaker'] ?? '';
    $dosen = $_POST['dosen'] ?? '';
    $date = $_POST['date'] ?? '';
    $time_start = $_POST['time_start'] ?? '09:00:00';
    $time_end = $_POST['time_end'] ?? '11:00:00';
    $location = $_POST['location'] ?? '';
    $quota_max = $_POST['quota_max'] ?? 20;
    $status = $_POST['status'] ?? 'active';

    if (empty($title) || empty($speaker) || empty($date)) {
        echo json_encode(['status' => 'error', 'message' => 'Judul, Pembicara, dan Tanggal wajib diisi']);
        exit;
    }

    $image = 'assets/default_seminar.jpg'; // Default

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "../uploads/seminars/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $image = "uploads/seminars/" . $fileName;
        }
    }

    $sql = "INSERT INTO seminars (title, speaker, dosen, date, time_start, time_end, location, quota_max, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssisss", $title, $speaker, $dosen, $date, $time_start, $time_end, $location, $quota_max, $image, $status);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Seminar berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}
$conn->close();
?>
