<?php
header('Content-Type: application/json');
require 'db.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id) {
    // Get Single Seminar
    $stmt = $conn->prepare("SELECT id, title, speaker, dosen, date, time_start, time_end, location, quota_current as quotaCurrent, quota_max as quotaMax, image, status FROM seminars WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc());
} else {
    // Get All Seminars
    $sql = "SELECT id, title, speaker, dosen, date, time_start, time_end, location, quota_current as quotaCurrent, quota_max as quotaMax, image, status FROM seminars ORDER BY date DESC";
    $result = $conn->query($sql);

    $seminars = [];
    while ($row = $result->fetch_assoc()) {
        $seminars[] = $row;
    }
    echo json_encode($seminars);
}

$conn->close();
?>
