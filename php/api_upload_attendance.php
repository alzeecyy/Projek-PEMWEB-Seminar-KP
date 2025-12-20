<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $registrationId = $_POST['registration_id'] ?? null;
    
    if (!$registrationId) {
        echo json_encode(['status' => 'error', 'message' => 'ID Registrasi tidak ditemukan']);
        exit;
    }

    // Verify registration belongs to user and is approved (waiting for attendance)
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE id = ? AND user_id = ? AND status IN ('approved', 'attendance_confirmed')");
    $stmt->bind_param("ii", $registrationId, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Registrasi tidak valid atau tidak diizinkan upload bukti.']);
        exit;
    }
    $stmt->close();

    // Handle File Upload
    if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload file bukti.']);
        exit;
    }

    $fileTmpPath = $_FILES['proof']['tmp_name'];
    $fileName = $_FILES['proof']['name'];
    $fileSize = $_FILES['proof']['size'];
    $fileType = $_FILES['proof']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
    if (in_array($fileExtension, $allowedfileExtensions)) {
        $newFileName = time() . '_proof_' . $registrationId . '.' . $fileExtension;
        $uploadFileDir = '../uploads/';
        $dest_path = $uploadFileDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            // Update Database
            $proofPath = 'uploads/' . $newFileName;
            $stmt = $conn->prepare("UPDATE registrations SET status = 'pending_verification', proof_file = ? WHERE id = ?");
            $stmt->bind_param("si", $proofPath, $registrationId);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Bukti kehadiran berhasil diupload! Menunggu verifikasi admin.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database: ' . $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file ke folder tujuan.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ekstensi file tidak diizinkan. Gunakan JPG/PNG.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Request method tidak valid.']);
}

$conn->close();
