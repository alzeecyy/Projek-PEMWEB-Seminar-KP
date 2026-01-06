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
    $date = $_POST['date'] ?? '';
    $time_start = $_POST['time_start'] ?? '09:00:00';
    $time_end = $_POST['time_end'] ?? '11:00:00';
    $location = $_POST['location'] ?? '';
    $dosen = $_POST['dosen'] ?? '';
    $speaker = $_POST['speaker'] ?? '';
    $quota_max = $_POST['quota_max'] ?? 20;

    if (!$id || empty($title) || empty($date) || empty($location)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Handle Image Upload (Optional)
    $imageUpdate = "";
    $params = [$title, $speaker, $date, $time_start, $time_end, $location, $dosen, $quota_max];
    $types = "sssssssi";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "../uploads/seminars/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
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

    $conn->begin_transaction();

    try {
        $sql = "UPDATE seminars SET title = ?, speaker = ?, date = ?, time_start = ?, time_end = ?, location = ?, dosen = ?, quota_max = ?, status = 'active' $imageUpdate WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        // 2. Get the proposer (user_id)
        $stmt = $conn->prepare("SELECT user_id, title FROM seminars WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $seminar = $res->fetch_assoc();
        $userId = $seminar['user_id'];
        $stmt->close();

        // 3. Send Notification if user_id exists
        if ($userId) {
            $msg = "Selamat! Pengajuan seminar Anda telah disetujui dan sekarang aktif di jadwal.";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $msg);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Seminar berhasil disetujui dan diaktifkan']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
    }
}
$conn->close();
?>
