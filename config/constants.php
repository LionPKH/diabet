<?php
// Нормы глюкозы (ммоль/л)
define('GLUCOSE_MIN_NORMAL', 3.9);
define('GLUCOSE_MAX_NORMAL', 7.2);
define('GLUCOSE_CRITICAL_LOW', 3.3);
define('GLUCOSE_CRITICAL_HIGH', 10.0);

// Настройки сессии
define('SESSION_TIMEOUT', 3600); // 1 час

// Названия сессионных переменных
define('SESS_DOCTOR_ID', 'doctor_id');
define('SESS_DOCTOR_NAME', 'doctor_name');
define('SESS_LAST_ACTIVITY', 'last_activity');
?>
