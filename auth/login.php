<?php
session_start();
require_once __DIR__ . '/../config/constants.php';

// Если уже авторизован, перенаправляем на dashboard
if (isset($_SESSION[SESS_DOCTOR_ID])) {
    header('Location: /diabetes_monitoring/monitoring/dashboard.php');
    exit();
}

$error = '';
if (isset($_GET['timeout'])) {
    $error = 'Сессия истекла. Пожалуйста, войдите снова.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - МИС Мониторинг Диабета</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .card {
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4">МИС Мониторинг Диабета</h3>
                    <h5 class="text-center text-muted mb-4">Вход для врачей</h5>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form action="authenticate.php" method="POST">
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="login" name="login" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                    
                    <div class="mt-4 text-center text-muted small">
                        <p>Тестовый доступ:<br>
                        Логин: <strong>doctor1</strong><br>
                        Пароль: <strong>admin</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
