<?php
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Suppress all warnings/errors to ensure clean JSON
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Start session to check login, then close immediately to release lock
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    session_write_close(); 

    // Get Data
    $seminarId = $_POST['seminar_id'] ?? null;
    $name = $_POST['nama'] ?? '';
    $nim = $_POST['nim'] ?? '';
    $angkatan = $_POST['angkatan'] ?? '';
    $jurusan = $_POST['jurusan'] ?? '';
    
    // Validation
    if (!$seminarId || !$name || !$nim || !$angkatan || !$jurusan) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Check if User exists (Optional: if we want to support guests we'd create a user, 
    // but here we assume we just record the registration details directly or link to existing user)
    // To keep it simple and consistent with schema which requires user_id:
    if (!$userId) {
        // Find user by NIM or Email logic could be here, but for now let's error if not logged in
        // OR creating a temporary user. 
        // Let's rely on the assumption the user is logged in (frontend checks it).
        // If testing without login, we might need a workaround.
        // For now:
        echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
        exit;
    }

    // 1. Check Quota and Status
    $stmt = $conn->prepare("SELECT quota_current, quota_max, status, date, time_end FROM seminars WHERE id = ?");
    $stmt->bind_param("i", $seminarId);
    $stmt->execute();
    $res = $stmt->get_result();
    $seminar = $res->fetch_assoc();
    $stmt->close();

    if (!$seminar) {
        echo json_encode(['status' => 'error', 'message' => 'Seminar tidak ditemukan']);
        exit;
    }

    // Real-time Status Check
    $now = new DateTime();
    $seminarEndStr = $seminar['date'] . ' ' . ($seminar['time_end'] ?? '23:59:59');
    $seminarEnd = new DateTime($seminarEndStr);

    if ($seminarEnd < $now) {
        echo json_encode(['status' => 'error', 'message' => 'Seminar sudah selesai dan tidak menerima pendaftaran lagi']);
        exit;
    }

    if ($seminar['quota_current'] >= $seminar['quota_max'] || $seminar['status'] == 'full') {
        echo json_encode(['status' => 'error', 'message' => 'Kuota seminar sudah penuh']);
        exit;
    }

    // 2. Check Duplicate Registration
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE user_id = ? AND seminar_id = ?");
    $stmt->bind_param("ii", $userId, $seminarId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah terdaftar di seminar ini']);
        exit;
    }
    $stmt->close();

    // 3. Register Transaction
    $conn->begin_transaction();

    try {
        // Insert Registration
        // Note: 'status' in registrations is 'pending' or 'approved'. 
        // Let's set to 'approved' immediately for simple quota demo, or 'pending'.
        // User asked for quota increase: usually happens on approval. 
        // But for simplicity, let's assume auto-confirm joining.
        $status = 'attendance_confirmed'; // or just 'pending' but we increment quota now? 
        // Ideally quota reserves a spot. So yes, increment now.
        
        $sqlReg = "INSERT INTO registrations (user_id, seminar_id, status) VALUES (?, ?, 'approved')";
        $stmt = $conn->prepare($sqlReg);
        $stmt->bind_param("ii", $userId, $seminarId);
        $stmt->execute();
        $stmt->close();

        // Update Quota
        $sqlUpdate = "UPDATE seminars SET quota_current = quota_current + 1 WHERE id = ?";
        $stmt = $conn->prepare($sqlUpdate);
        $stmt->bind_param("i", $seminarId);
        $stmt->execute();
        $stmt->close();

        // Check if full to update status
        if ($seminar['quota_current'] + 1 >= $seminar['quota_max']) {
            $conn->query("UPDATE seminars SET status = 'full' WHERE id = $seminarId");
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Berhasil mendaftar serimar! Kuota telah diperbarui.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar: ' . $e->getMessage()]);
    }

}
$conn->close();
?>
