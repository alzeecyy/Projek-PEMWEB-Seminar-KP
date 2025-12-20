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

    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID proposal tidak ditemukan']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Get the proposer (user_id) before deleting
        $stmt = $conn->prepare("SELECT user_id, title FROM seminars WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Proposal tidak ditemukan atau sudah diproses']);
            exit;
        }
        
        $seminar = $res->fetch_assoc();
        $userId = $seminar['user_id'];
        $title = $seminar['title'];
        $stmt->close();

        // 2. Delete the proposal (or update status to 'rejected' if you want to keep records)
        // Option A: Delete completely
        $stmt = $conn->prepare("DELETE FROM seminars WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Option B: Update status to 'rejected' (uncomment if you prefer this)
        // $stmt = $conn->prepare("UPDATE seminars SET status = 'rejected' WHERE id = ?");
        // $stmt->bind_param("i", $id);
        // $stmt->execute();
        // $stmt->close();

        // 3. Send Notification to proposer
        if ($userId) {
            $msg = "Mohon maaf, pengajuan seminar Anda ditolak oleh admin. Silakan periksa kembali dokumen dan persyaratan yang dibutuhkan.";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $msg);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Proposal berhasil ditolak dan pengaju telah diberi notifikasi']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
    }
}
$conn->close();
?>
