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
    $action = $_POST['action'] ?? '';

    if (!$id || !$action) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Get User ID and Seminar Title for notification
        $stmt = $conn->prepare("SELECT r.user_id, s.title FROM registrations r JOIN seminars s ON r.seminar_id = s.id WHERE r.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $reg = $res->fetch_assoc();
        $userId = $reg['user_id'];
        $title = $reg['title'];
        $stmt->close();

        if ($action === 'approve') {
            // Update to attended
            $stmt = $conn->prepare("UPDATE registrations SET status = 'attended' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Send notification
            $msg = "Selamat! Bukti kehadiran seminar Anda telah disetujui. Poin/Statistik Anda telah diperbarui.";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $msg);
            $stmt->execute();
            $stmt->close();

        } else if ($action === 'reject') {
            // Update back to approved (so they can upload again)
            $stmt = $conn->prepare("UPDATE registrations SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Send notification
            $msg = "Mohon maaf, bukti kehadiran Anda ditolak. Silakan upload kembali bukti yang valid.";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $msg);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Berhasil memproses verifikasi.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
    }
}
$conn->close();
