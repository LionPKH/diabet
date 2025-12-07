<?php
session_start();
require_once __DIR__ . '/../config/constants.php';

// Проверка существования сессии
if (!isset($_SESSION[SESS_DOCTOR_ID])) {
    header('Location: /diabetes_monitoring/auth/login.php');
    exit();
}

// Проверка таймаута сессии
if (isset($_SESSION[SESS_LAST_ACTIVITY]) && (time() - $_SESSION[SESS_LAST_ACTIVITY] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: /diabetes_monitoring/auth/login.php?timeout=1');
    exit();
}

// Обновление времени последней активности
$_SESSION[SESS_LAST_ACTIVITY] = time();
?>
