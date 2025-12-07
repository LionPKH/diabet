// Общие функции для всего приложения

// Автоматическое обновление времени на странице
function updateTimestamps() {
    const now = new Date();
    document.querySelectorAll('.timestamp').forEach(el => {
        const timestamp = new Date(el.dataset.time);
        const diff = Math.floor((now - timestamp) / 1000);
        
        if (diff < 60) {
            el.textContent = 'только что';
        } else if (diff < 3600) {
            el.textContent = Math.floor(diff / 60) + ' мин. назад';
        } else if (diff < 86400) {
            el.textContent = Math.floor(diff / 3600) + ' ч. назад';
        }
    });
}

// Обновление оповещений каждые 30 секунд
function checkNewAlerts() {
    fetch('/diabetes_monitoring/alerts/ajax_get_alerts.php')
        .then(response => response.json())
        .then(data => {
            if (data.unread_count > 0) {
                const badge = document.querySelector('.navbar .badge');
                if (badge) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'inline';
                }
            }
        })
        .catch(error => console.error('Error checking alerts:', error));
}

// Подтверждение критических действий
function confirmAction(message) {
    return confirm(message);
}

// Форматирование чисел
function formatNumber(num, decimals = 1) {
    return parseFloat(num).toFixed(decimals);
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Обновление временных меток каждую минуту
    setInterval(updateTimestamps, 60000);
    
    // Проверка новых оповещений каждые 30 секунд
    setInterval(checkNewAlerts, 30000);
    
    // Автозакрытие уведомлений через 5 секунд
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Добавление анимации при наведении на карточки
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'transform 0.2s';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// Экспорт данных в CSV
function exportToCSV(tableId, filename = 'data.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => csvRow.push(col.textContent));
        csv.push(csvRow.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
}

// Печать страницы
function printPage() {
    window.print();
}

// Копирование текста в буфер обмена
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Скопировано в буфер обмена');
    }).catch(err => {
        console.error('Ошибка копирования:', err);
    });
}
