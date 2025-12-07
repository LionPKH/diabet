<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alerts_panel.php');
    exit();
}

$patient_id = $_POST['patient_id'] ?? 0;
$recommendation_text = $_POST['recommendation_text'] ?? '';
$recommended_insulin_dose = $_POST['recommended_insulin_dose'] ?? null;
$doctor_id = $_SESSION[SESS_DOCTOR_ID];

if (empty($recommendation_text)) {
    header('Location: alerts_panel.php?error=empty');
    exit();
}

// Если доза пустая, делаем NULL
if (empty($recommended_insulin_dose)) {
    $recommended_insulin_dose = null;
}

$conn = getDBConnection();

// Проверяем, что пациент принадлежит врачу
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $patient_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: alerts_panel.php?error=access');
    exit();
}
$stmt->close();

// Сохраняем рекомендацию
$stmt = $conn->prepare("INSERT INTO recommendations (patient_id, doctor_id, recommendation_text, recommended_insulin_dose) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iisd", $patient_id, $doctor_id, $recommendation_text, $recommended_insulin_dose);
$success = $stmt->execute();

$stmt->close();
$conn->close();

if ($success) {
    header('Location: alerts_panel.php?success=1');
} else {
    header('Location: alerts_panel.php?error=save');
}
exit();
?>
