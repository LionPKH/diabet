<?php
$pageTitle = 'Список пациентов';
include __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$doctor_id = $_SESSION[SESS_DOCTOR_ID];

// Поиск и фильтры
$search = $_GET['search'] ?? '';
$diabetes_type = $_GET['diabetes_type'] ?? '';

$sql = "SELECT p.*, 
        (SELECT glucose_level FROM glucose_readings WHERE patient_id = p.patient_id ORDER BY measurement_time DESC LIMIT 1) as last_glucose,
        (SELECT measurement_time FROM glucose_readings WHERE patient_id = p.patient_id ORDER BY measurement_time DESC LIMIT 1) as last_measurement
        FROM patients p 
        WHERE p.doctor_id = ?";

$params = [$doctor_id];
$types = "i";

if (!empty($search)) {
    $sql .= " AND p.full_name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($diabetes_type)) {
    $sql .= " AND p.diabetes_type = ?";
    $params[] = $diabetes_type;
    $types .= "s";
}

$sql .= " ORDER BY p.full_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$patients = $stmt->get_result();
?>

<div class="row">
    <div class="col-12">
        <h2><i class="bi bi-people"></i> Мои пациенты</h2>
        
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Поиск по ФИО..." value="<?= clean($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="diabetes_type">
                            <option value="">Все типы диабета</option>
                            <option value="type1" <?= $diabetes_type == 'type1' ? 'selected' : '' ?>>Тип 1</option>
                            <option value="type2" <?= $diabetes_type == 'type2' ? 'selected' : '' ?>>Тип 2</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Найти</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Возраст</th>
                                <th>Тип диабета</th>
                                <th>Последнее измерение</th>
                                <th>Глюкоза</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td><?= $patient['patient_id'] ?></td>
                                <td><?= clean($patient['full_name']) ?></td>
                                <td><?= calculateAge($patient['birth_date']) ?> лет</td>
                                <td>
                                    <span class="badge bg-info"><?= getDiabetesTypeRu($patient['diabetes_type']) ?></span>
                                </td>
                                <td>
                                    <?php if ($patient['last_measurement']): ?>
                                        <?= formatDate($patient['last_measurement']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Нет данных</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patient['last_glucose']): 
                                        $status = getGlucoseStatus($patient['last_glucose']);
                                    ?>
                                        <span class="badge bg-<?= $status['class'] ?>">
                                            <?= $patient['last_glucose'] ?> ммоль/л
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/diabetes_monitoring/monitoring/dashboard.php?patient_id=<?= $patient['patient_id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-graph-up"></i> Мониторинг
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($patients->num_rows == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Пациенты не найдены</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
include __DIR__ . '/../includes/footer.php';
?>
