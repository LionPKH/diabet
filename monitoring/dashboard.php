<?php
$pageTitle = 'Мониторинг пациента';
include __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$doctor_id = $_SESSION[SESS_DOCTOR_ID];

// Выбор пациента
$patient_id = $_GET['patient_id'] ?? null;

// Получаем список пациентов врача
$stmt = $conn->prepare("SELECT patient_id, full_name FROM patients WHERE doctor_id = ? ORDER BY full_name");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients_list = $stmt->get_result();
$stmt->close();

// Если пациент не выбран, берем первого
if (!$patient_id && $patients_list->num_rows > 0) {
    $patients_list->data_seek(0);
    $patient_id = $patients_list->fetch_assoc()['patient_id'];
    $patients_list->data_seek(0);
}

// Получаем данные пациента
$patient = null;
if ($patient_id) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $patient_id, $doctor_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Временной диапазон
$period = $_GET['period'] ?? '7days';
$interval = '7 DAY';
switch($period) {
    case '24hours':
        $interval = '1 DAY';
        break;
    case '30days':
        $interval = '30 DAY';
        break;
}

// Статистика
$stats = null;
if ($patient_id) {
    $glucose_min = GLUCOSE_MIN_NORMAL;
    $glucose_max = GLUCOSE_MAX_NORMAL;
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_readings,
            AVG(glucose_level) as avg_glucose,
            MIN(glucose_level) as min_glucose,
            MAX(glucose_level) as max_glucose,
            SUM(CASE WHEN glucose_level < ? THEN 1 ELSE 0 END) as low_count,
            SUM(CASE WHEN glucose_level > ? THEN 1 ELSE 0 END) as high_count
        FROM glucose_readings 
        WHERE patient_id = ? AND measurement_time >= DATE_SUB(NOW(), INTERVAL $interval)
    ");
    $stmt->bind_param("ddi", $glucose_min, $glucose_max, $patient_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5><i class="bi bi-person"></i> Выбор пациента</h5>
            </div>
            <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                <?php while ($p = $patients_list->fetch_assoc()): ?>
                <a href="?patient_id=<?= $p['patient_id'] ?>&period=<?= $period ?>" 
                   class="list-group-item list-group-item-action <?= $p['patient_id'] == $patient_id ? 'active' : '' ?>">
                    <?= clean($p['full_name']) ?>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <?php if ($patient): ?>
        
        <div class="card mb-3">
            <div class="card-body">
                <h4><?= clean($patient['full_name']) ?></h4>
                <p class="mb-1">
                    <strong>Возраст:</strong> <?= calculateAge($patient['birth_date']) ?> лет 
                    (<?= formatDateOnly($patient['birth_date']) ?>)
                </p>
                <p class="mb-1">
                    <strong>Тип диабета:</strong> 
                    <span class="badge bg-info"><?= getDiabetesTypeRu($patient['diabetes_type']) ?></span>
                </p>
                <p class="mb-0">
                    <strong>Контакты:</strong> <?= clean($patient['phone']) ?>, <?= clean($patient['email']) ?>
                </p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> График уровня глюкозы</h5>
                <div class="btn-group" role="group">
                    <a href="?patient_id=<?= $patient_id ?>&period=24hours" 
                       class="btn btn-sm btn-outline-primary <?= $period == '24hours' ? 'active' : '' ?>">24 часа</a>
                    <a href="?patient_id=<?= $patient_id ?>&period=7days" 
                       class="btn btn-sm btn-outline-primary <?= $period == '7days' ? 'active' : '' ?>">7 дней</a>
                    <a href="?patient_id=<?= $patient_id ?>&period=30days" 
                       class="btn btn-sm btn-outline-primary <?= $period == '30days' ? 'active' : '' ?>">30 дней</a>
                </div>
            </div>
            <div class="card-body">
                <canvas id="glucoseChart" height="100"></canvas>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Всего измерений</h6>
                        <h3><?= $stats['total_readings'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Средний уровень</h6>
                        <h3><?= number_format($stats['avg_glucose'], 1) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Пониженных</h6>
                        <h3 class="text-danger"><?= $stats['low_count'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Повышенных</h6>
                        <h3 class="text-warning"><?= $stats['high_count'] ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-droplet"></i> Последние измерения глюкозы</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Время</th>
                                        <th>Уровень</th>
                                        <th>Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT * FROM glucose_readings WHERE patient_id = ? ORDER BY measurement_time DESC LIMIT 10");
                                    $stmt->bind_param("i", $patient_id);
                                    $stmt->execute();
                                    $readings = $stmt->get_result();
                                    while ($reading = $readings->fetch_assoc()):
                                        $status = getGlucoseStatus($reading['glucose_level']);
                                    ?>
                                    <tr>
                                        <td><?= formatDate($reading['measurement_time']) ?></td>
                                        <td><?= $reading['glucose_level'] ?> ммоль/л</td>
                                        <td><span class="badge bg-<?= $status['class'] ?>"><?= $status['text'] ?></span></td>
                                    </tr>
                                    <?php endwhile; $stmt->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-syringe"></i> История введения инсулина</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Время</th>
                                        <th>Доза</th>
                                        <th>Тип</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT * FROM insulin_doses WHERE patient_id = ? ORDER BY administration_time DESC LIMIT 10");
                                    $stmt->bind_param("i", $patient_id);
                                    $stmt->execute();
                                    $doses = $stmt->get_result();
                                    while ($dose = $doses->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?= formatDate($dose['administration_time']) ?></td>
                                        <td><?= $dose['insulin_amount'] ?> ЕД</td>
                                        <td><?= clean($dose['insulin_type']) ?></td>
                                    </tr>
                                    <?php endwhile; $stmt->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <div class="alert alert-info">
            <h5>Выберите пациента для мониторинга</h5>
            <p>Выберите пациента из списка слева для просмотра показателей.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($patient): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Получаем данные для графика
    fetch('/diabetes_monitoring/monitoring/ajax_get_glucose_data.php?patient_id=<?= $patient_id ?>&period=<?= $period ?>')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('glucoseChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Уровень глюкозы',
                        data: data.values,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1,
                        fill: true
                    }, {
                        label: 'Верхняя норма',
                        data: Array(data.labels.length).fill(<?= GLUCOSE_MAX_NORMAL ?>),
                        borderColor: 'rgba(255, 206, 86, 0.5)',
                        borderDash: [5, 5],
                        pointRadius: 0,
                        fill: false
                    }, {
                        label: 'Нижняя норма',
                        data: Array(data.labels.length).fill(<?= GLUCOSE_MIN_NORMAL ?>),
                        borderColor: 'rgba(255, 206, 86, 0.5)',
                        borderDash: [5, 5],
                        pointRadius: 0,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'ммоль/л'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Время измерения'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        });
});
</script>
<?php endif; ?>

<?php
$conn->close();
include __DIR__ . '/../includes/footer.php';
?>