<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$doctor_id = $_SESSION[SESS_DOCTOR_ID];
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE doctor_id = ? AND is_acknowledged = FALSE");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$stmt->close();
$conn->close();

echo json_encode([
    'unread_count' => $result['count']
]);
?>
