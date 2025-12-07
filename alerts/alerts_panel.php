<?php
$pageTitle = 'Оповещения';
include __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$doctor_id = $_SESSION[SESS_DOCTOR_ID];

// Фильтр
$show_acknowledged = $_GET['show_acknowledged'] ?? 'false';

$sql = "SELECT a.*, p.full_name, p.diabetes_type 
        FROM alerts a 
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ?";

if ($show_acknowledged === 'false') {
    $sql .= " AND a.is_acknowledged = FALSE";
}

$sql .= " ORDER BY a.alert_time DESC LIMIT 50";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$alerts = $stmt->get_result();
?>

<div class="row">
    <div class="col-12">
        <h2><i class="bi bi-bell"></i> Оповещения</h2>
        
        <div class="card mb-3">
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showAcknowledged" 
                           <?= $show_acknowledged === 'true' ? 'checked' : '' ?>
                           onchange="window.location.href='?show_acknowledged=' + (this.checked ? 'true' : 'false')">
                    <label class="form-check-label" for="showAcknowledged">
                        Показать обработанные оповещения
                    </label>
                </div>
            </div>
        </div>
        
        <?php if ($alerts->num_rows > 0): ?>
        <div class="row">
            <?php while ($alert = $alerts->fetch_assoc()): 
                $alertClass = getAlertClass($alert['alert_type']);
                $alertText = getAlertTypeText($alert['alert_type']);
            ?>
            <div class="col-md-6 mb-3">
                <div class="card border-<?= $alertClass ?>">
                    <div class="card-header bg-<?= $alertClass ?> text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-exclamation-triangle"></i> <?= $alertText ?></span>
                        <?php if (!$alert['is_acknowledged']): ?>
                        <span class="badge bg-light text-dark">Новое</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5><?= clean($alert['full_name']) ?></h5>
                        <p class="mb-2">
                            <strong>Уровень глюкозы:</strong> 
                            <span class="badge bg-<?= $alertClass ?>"><?= $alert['glucose_value'] ?> ммоль/л</span>
                        </p>
                        <p class="mb-2">
                            <strong>Время:</strong> <?= formatDate($alert['alert_time']) ?>
                        </p>
                        <p class="mb-3">
                            <strong>Тип диабета:</strong> 
                            <span class="badge bg-info"><?= getDiabetesTypeRu($alert['diabetes_type']) ?></span>
                        </p>
                        
                        <div class="btn-group w-100" role="group">
                            <?php if (!$alert['is_acknowledged']): ?>
                            <button class="btn btn-outline-success" onclick="acknowledgeAlert(<?= $alert['alert_id'] ?>)">
                                <i class="bi bi-check-circle"></i> Обработано
                            </button>
                            <?php endif; ?>
                            <a href="/diabetes_monitoring/monitoring/dashboard.php?patient_id=<?= $alert['patient_id'] ?>" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-graph-up"></i> Мониторинг
                            </a>
                            <button class="btn btn-outline-info" 
                                    onclick="showRecommendationModal(<?= $alert['patient_id'] ?>, '<?= clean($alert['full_name']) ?>', <?= $alert['glucose_value'] ?>)">
                                <i class="bi bi-pencil"></i> Рекомендация
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Нет активных оповещений
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для создания рекомендации -->
<div class="modal fade" id="recommendationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Создать рекомендацию</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recommendationForm" method="POST" action="save_recommendation.php">
                <div class="modal-body">
                    <input type="hidden" name="patient_id" id="rec_patient_id">
                    <div class="mb-3">
                        <label class="form-label"><strong>Пациент:</strong></label>
                        <p id="rec_patient_name"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Текущий уровень глюкозы:</strong></label>
                        <p id="rec_glucose_level"></p>
                    </div>
                    <div class="mb-3">
                        <label for="recommendation_text" class="form-label">Рекомендация:</label>
                        <textarea class="form-control" id="recommendation_text" name="recommendation_text" 
                                  rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="recommended_insulin_dose" class="form-label">Рекомендуемая доза инсулина (ЕД):</label>
                        <input type="number" step="0.5" class="form-control" id="recommended_insulin_dose" 
                               name="recommended_insulin_dose">
                        <small class="text-muted">Оставьте пустым, если коррекция дозы не требуется</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function acknowledgeAlert(alertId) {
    if (confirm('Отметить оповещение как обработанное?')) {
        fetch('acknowledge_alert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'alert_id=' + alertId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка при обработке оповещения');
            }
        });
    }
}

function showRecommendationModal(patientId, patientName, glucoseLevel) {
    document.getElementById('rec_patient_id').value = patientId;
    document.getElementById('rec_patient_name').textContent = patientName;
    document.getElementById('rec_glucose_level').textContent = glucoseLevel + ' ммоль/л';
    
    // Предзаполнение рекомендации в зависимости от уровня
    let recommendation = '';
    if (glucoseLevel < <?= GLUCOSE_CRITICAL_LOW ?>) {
        recommendation = 'Срочно принять 15г быстрых углеводов (сок, сахар). Повторить измерение через 15 минут. При сохранении симптомов обратиться к врачу.';
    } else if (glucoseLevel > <?= GLUCOSE_CRITICAL_HIGH ?>) {
        recommendation = 'Проверить кетоны. Увеличить дозу инсулина согласно схеме коррекции. Пить больше воды. Контроль глюкозы каждые 2 часа.';
    }
    document.getElementById('recommendation_text').value = recommendation;
    
    const modal = new bootstrap.Modal(document.getElementById('recommendationModal'));
    modal.show();
}
</script>

<?php
$stmt->close();
$conn->close();
include __DIR__ . '/../includes/footer.php';
?>
