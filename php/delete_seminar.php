<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

// Check Admin Access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan']);
        exit;
    }

    // Begin Transaction (Optional, but good for safety if we delete related data manually)
    // However, if foreign keys are set to valid constraints (e.g., CASCADE or RESTRICT), we handle that.
    
    // For now, simple delete. Note: If registrations exist, this might fail unless ON DELETE CASCADE.
    // Let's assume we want to delete related registrations first or we rely on DB definition.
    // To be safe, let's delete registrations first.
    
    $conn->begin_transaction();

    try {
        // Delete Registrations first
        $stmt = $conn->prepare("DELETE FROM registrations WHERE seminar_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Delete Seminar
        $stmt = $conn->prepare("DELETE FROM seminars WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Seminar berhasil dihapus']);
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Seminar tidak ditemukan atau gagal dihapus']);
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
$conn->close();
?>
