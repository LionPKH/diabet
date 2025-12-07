<?php
// Главная страница - перенаправление
session_start();
require_once __DIR__ . '/config/constants.php';

if (isset($_SESSION[SESS_DOCTOR_ID])) {
    header('Location: /diabetes_monitoring/monitoring/dashboard.php');
} else {
    header('Location: /diabetes_monitoring/auth/login.php');
}
exit();
?>
