<?php
// Проверка статуса глюкозы
function getGlucoseStatus($level) {
    if ($level < GLUCOSE_CRITICAL_LOW) {
        return ['status' => 'critical_low', 'class' => 'danger', 'text' => 'Критически низкий'];
    } elseif ($level < GLUCOSE_MIN_NORMAL) {
        return ['status' => 'warning_low', 'class' => 'warning', 'text' => 'Пониженный'];
    } elseif ($level <= GLUCOSE_MAX_NORMAL) {
        return ['status' => 'normal', 'class' => 'success', 'text' => 'Норма'];
    } elseif ($level <= GLUCOSE_CRITICAL_HIGH) {
        return ['status' => 'warning_high', 'class' => 'warning', 'text' => 'Повышенный'];
    } else {
        return ['status' => 'critical_high', 'class' => 'danger', 'text' => 'Критически высокий'];
    }
}

// Защита от XSS
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Форматирование даты
function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

// Форматирование даты только день
function formatDateOnly($date) {
    return date('d.m.Y', strtotime($date));
}

// Вычисление возраста
function calculateAge($birthDate) {
    $birth = new DateTime($birthDate);
    $now = new DateTime();
    return $birth->diff($now)->y;
}

// Получение типа диабета на русском
function getDiabetesTypeRu($type) {
    return $type == 'type1' ? 'Тип 1' : 'Тип 2';
}

// Получение класса для типа оповещения
function getAlertClass($type) {
    switch($type) {
        case 'critical_high':
        case 'critical_low':
            return 'danger';
        case 'warning':
            return 'warning';
        default:
            return 'info';
    }
}

// Получение текста типа оповещения
function getAlertTypeText($type) {
    switch($type) {
        case 'critical_high':
            return 'Критическая гипергликемия';
        case 'critical_low':
            return 'Критическая гипогликемия';
        case 'warning':
            return 'Предупреждение';
        default:
            return 'Информация';
    }
}
?>
