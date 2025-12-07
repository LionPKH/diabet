<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$alert_id = $_POST['alert_id'] ?? 0;
$doctor_id = $_SESSION[SESS_DOCTOR_ID];

$conn = getDBConnection();

// Проверяем, что оповещение принадлежит врачу
$stmt = $conn->prepare("UPDATE alerts SET is_acknowledged = TRUE WHERE alert_id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $alert_id, $doctor_id);
$success = $stmt->execute();

$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
?>
