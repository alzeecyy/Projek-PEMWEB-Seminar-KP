<?php
header('Content-Type: application/json');
require 'db.php';

$stmt = $conn->prepare('SELECT id, name, email, role FROM users');
$stmt->execute();
$res = $stmt->get_result();
$users = [];
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode(['status' => 'success', 'data' => $users]);
?>
