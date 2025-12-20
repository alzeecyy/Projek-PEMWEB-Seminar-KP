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
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $speaker = $_POST['speaker'] ?? '';
    $dosen = $_POST['dosen'] ?? '';
    $date = $_POST['date'] ?? '';
    $time_start = $_POST['time_start'] ?? '09:00:00';
    $time_end = $_POST['time_end'] ?? '11:00:00';
    $location = $_POST['location'] ?? '';
    $quota_max = $_POST['quota_max'] ?? 20;

    if (!$id || empty($title) || empty($speaker) || empty($date)) {
        echo json_encode(['status' => 'error', 'message' => 'ID, Judul, Pembicara, dan Tanggal wajib diisi']);
        exit;
    }

    // Handle Image Update (Optional)
    $imageUpdate = "";
    $params = [$title, $speaker, $dosen, $date, $time_start, $time_end, $location, $quota_max];
    $types = "ssssssis";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "../uploads/seminars/";
        $fileName = time() . '_' . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = "uploads/seminars/" . $fileName;
            $imageUpdate = ", image = ?";
            $params[] = $imagePath;
            $types .= "s";
        }
    }

    $params[] = $id;
    $types .= "i";

    $sql = "UPDATE seminars SET title = ?, speaker = ?, dosen = ?, date = ?, time_start = ?, time_end = ?, location = ?, quota_max = ? $imageUpdate WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data seminar berhasil diperbarui']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}
$conn->close();
?>
