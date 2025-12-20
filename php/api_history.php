<?php
header('Content-Type: application/json');
require 'db.php';
session_start();

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Fetch Combined History (Registrations + Proposals)
$sql = "
    (SELECT 
        r.id, 
        r.status, 
        r.registered_at as timestamp, 
        s.title, 
        s.date, 
        s.time_start,
        s.time_end,
        'participant' as type 
    FROM registrations r
    JOIN seminars s ON r.seminar_id = s.id
    WHERE r.user_id = ?)
    UNION ALL
    (SELECT 
        id, 
        status, 
        created_at as timestamp, 
        title, 
        date, 
        time_start,
        time_end,
        'proposer' as type 
    FROM seminars 
    WHERE user_id = ?)
    ORDER BY timestamp DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'data' => $history
]);

$conn->close();
?>
