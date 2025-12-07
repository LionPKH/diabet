<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    header('Location: login.php?error=empty');
    exit();
}

$conn = getDBConnection();

// Подготовленный запрос для защиты от SQL-инъекций
$stmt = $conn->prepare("SELECT doctor_id, password_hash, full_name FROM doctors WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();
    
    // Проверка пароля
    if (password_verify($password, $doctor['password_hash'])) {
        // Успешная авторизация
        $_SESSION[SESS_DOCTOR_ID] = $doctor['doctor_id'];
        $_SESSION[SESS_DOCTOR_NAME] = $doctor['full_name'];
        $_SESSION[SESS_LAST_ACTIVITY] = time();
        
        $stmt->close();
        $conn->close();
        
        header('Location: /diabetes_monitoring/monitoring/dashboard.php');
        exit();
    }
}

// Неверные данные
$stmt->close();
$conn->close();

header('Location: login.php?error=invalid');
exit();
?>
