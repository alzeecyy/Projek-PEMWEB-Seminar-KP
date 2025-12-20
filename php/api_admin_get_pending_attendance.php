<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

// Check Admin Access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

$sql = "SELECT r.id, u.name as user_name, s.title as seminar_name, r.proof_file 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        JOIN seminars s ON r.seminar_id = s.id 
        WHERE r.status = 'pending_verification'";

$res = $conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(['status' => 'success', 'data' => $data]);
$conn->close();
