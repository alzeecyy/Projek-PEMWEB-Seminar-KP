<?php
ob_start();
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
session_start();
require 'db.php';

// Optimization for large uploads
ini_set('memory_limit', '256M');
set_time_limit(300); // 5 minutes

$userId = $_SESSION['user_id'] ?? null;
session_write_close(); // Release session lock early to prevent blocking

// Check if logged in
if (!$userId) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk mendaftar.']);
    exit;
}

// Prevent multiple pending proposals
$checkSql = "SELECT id FROM seminars WHERE user_id = ? AND status = 'pending'";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $userId);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Anda sudah memiliki pengajuan yang sedang diproses (pending).']);
    exit;
}
$checkStmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Basic Data
    $name = $_POST['nama'] ?? '';
    $nim = $_POST['nim'] ?? '';
    $email = $_POST['email'] ?? '';
    $angkatan = $_POST['angkatan'] ?? '';
    $jurusan = $_POST['jurusan'] ?? '';
    
    // Validate
    if (empty($name) || empty($nim) || empty($angkatan) || empty($jurusan)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Data diri tidak lengkap']);
        exit;
    }

    // Handle File Uploads
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileFields = ['file1', 'file2', 'file3', 'file4', 'file5', 'file6'];
    $uploadedFiles = [];
    $error = false;
    $msg = '';

    foreach ($fileFields as $field) {
        if (!isset($_FILES[$field])) {
            $error = true;
            $msg = "File $field wajib diupload (Tidak terdeteksi).";
            break;
        }

        $errCode = $_FILES[$field]['error'];

        if ($errCode === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . basename($_FILES[$field]['name']);
            $targetPath = $uploadDir . $fileName;
            
            // Allow certain file formats
            $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
            $allowTypes = array('jpg', 'png', 'jpeg', 'pdf');
            
            if (in_array($fileType, $allowTypes)) {
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
                    $uploadedFiles[$field] = 'uploads/' . $fileName; // Store relative path
                } else {
                    $error = true;
                    $msg = "Gagal memindahkan file $field ke folder server.";
                    break;
                }
            } else {
                $error = true;
                $msg = "Format file $field tidak valid (hanya JPG, JPEG, PNG, PDF)";
                break;
            }
        } elseif ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $error = true;
            $msg = "File $field terlalu besar (Melebihi batas server).";
            break;
        } elseif ($errCode === UPLOAD_ERR_NO_FILE) {
            $error = true;
            $msg = "File $field belum dipilih/diupload.";
            break;
        } else {
            $error = true;
            $msg = "Error upload pada file $field. Kode Error: $errCode";
            break;
        }
    }

    if ($error) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit;
    }

    // Prepare JSON for documents
    $proofFilesJson = json_encode($uploadedFiles);
    
    // Default values for new proposal
    $title = "Seminar KP oleh $name"; 
    $speaker = $name;
    $dosen = "Belum Ditentukan"; 
    $date = date('Y-m-d', strtotime('+1 month')); 
    $location = "Menunggu Konfirmasi";
    $quota_max = 20;
    $image = 'assets/default_seminar.jpg'; 

    $sql = "INSERT INTO seminars (user_id, title, speaker, dosen, date, location, quota_max, image, documents, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Query error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("isssssiss", $userId, $title, $speaker, $dosen, $date, $location, $quota_max, $image, $proofFilesJson);
    
    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode(['status' => 'success', 'message' => 'Pengajuan Seminar Berhasil! Menunggu persetujuan admin.']);
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
}
$conn->close();
