<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'МИС Мониторинг Диабета' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/diabetes_monitoring/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/diabetes_monitoring/monitoring/dashboard.php">
                <i class="bi bi-activity"></i> МИС Мониторинг Диабета
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/diabetes_monitoring/monitoring/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Мониторинг
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/diabetes_monitoring/patients/patients_list.php">
                            <i class="bi bi-people"></i> Пациенты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/diabetes_monitoring/alerts/alerts_panel.php">
                            <i class="bi bi-bell"></i> Оповещения
                            <?php
                            $conn = getDBConnection();
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE doctor_id = ? AND is_acknowledged = FALSE");
                            $stmt->bind_param("i", $_SESSION[SESS_DOCTOR_ID]);
                            $stmt->execute();
                            $unread = $stmt->get_result()->fetch_assoc()['count'];
                            $stmt->close();
                            $conn->close();
                            if ($unread > 0):
                            ?>
                            <span class="badge bg-danger"><?= $unread ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= clean($_SESSION[SESS_DOCTOR_NAME]) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/diabetes_monitoring/auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Выход
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid mt-4">
