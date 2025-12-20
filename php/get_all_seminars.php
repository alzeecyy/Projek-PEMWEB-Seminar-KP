<?php
header('Content-Type: application/json');
require 'db.php';

$stmt = $conn->prepare('SELECT id, user_id, title, speaker, date, time_start, time_end, location, dosen, quota_current, quota_max, status, image, documents FROM seminars');
$stmt->execute();
$res = $stmt->get_result();
$seminars = [];
while ($row = $res->fetch_assoc()) {
    $seminars[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode(['status' => 'success', 'data' => $seminars]);
?>
