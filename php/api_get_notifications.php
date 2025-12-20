<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch notifications
$stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$notifications = [];
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

echo json_encode(['status' => 'success', 'data' => $notifications]);
$conn->close();
?>
