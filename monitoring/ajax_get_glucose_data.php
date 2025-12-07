<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$patient_id = $_GET['patient_id'] ?? 0;
$period = $_GET['period'] ?? '7days';
$doctor_id = $_SESSION[SESS_DOCTOR_ID];

$interval = '7 DAY';
switch($period) {
    case '24hours':
        $interval = '1 DAY';
        $date_format = '%H:%i';
        break;
    case '30days':
        $interval = '30 DAY';
        $date_format = '%d.%m';
        break;
    default:
        $date_format = '%d.%m %H:%i';
}

$conn = getDBConnection();

// Проверяем, что пациент принадлежит врачу
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $patient_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Access denied']);
    exit();
}
$stmt->close();

// Получаем данные глюкозы
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(measurement_time, '$date_format') as time_label,
        glucose_level,
        measurement_time
    FROM glucose_readings 
    WHERE patient_id = ? 
        AND measurement_time >= DATE_SUB(NOW(), INTERVAL $interval)
    ORDER BY measurement_time ASC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['time_label'];
    $values[] = floatval($row['glucose_level']);
}

$stmt->close();
$conn->close();

echo json_encode([
    'labels' => $labels,
    'values' => $values
]);
?>
