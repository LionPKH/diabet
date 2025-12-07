-- Создание базы данных
CREATE DATABASE IF NOT EXISTS diabetes_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diabetes_monitoring;

-- Таблица врачей
CREATE TABLE doctors (
    doctor_id INT PRIMARY KEY AUTO_INCREMENT,
    login VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(50) DEFAULT 'Эндокринолог',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица пациентов
CREATE TABLE patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    birth_date DATE,
    diabetes_type ENUM('type1', 'type2') NOT NULL,
    doctor_id INT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_doctor (doctor_id),
    INDEX idx_diabetes_type (diabetes_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица показателей глюкозы
CREATE TABLE glucose_readings (
    reading_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    glucose_level DECIMAL(5,2) NOT NULL,
    measurement_time DATETIME NOT NULL,
    sensor_id VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    INDEX idx_patient_time (patient_id, measurement_time),
    INDEX idx_measurement_time (measurement_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица доз инсулина
CREATE TABLE insulin_doses (
    dose_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    insulin_amount DECIMAL(5,2) NOT NULL,
    insulin_type VARCHAR(50),
    administration_time DATETIME NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    INDEX idx_patient_time (patient_id, administration_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица оповещений
CREATE TABLE alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    alert_type ENUM('critical_high', 'critical_low', 'warning') NOT NULL,
    glucose_value DECIMAL(5,2) NOT NULL,
    alert_time DATETIME NOT NULL,
    is_acknowledged BOOLEAN DEFAULT FALSE,
    doctor_id INT NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_doctor_ack (doctor_id, is_acknowledged),
    INDEX idx_alert_time (alert_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица рекомендаций
CREATE TABLE recommendations (
    recommendation_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    recommendation_text TEXT NOT NULL,
    recommended_insulin_dose DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Вставка тестовых данных
-- Врач (пароль: doctor123)
INSERT INTO doctors (login, password_hash, full_name, specialization) VALUES
('doctor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Иванов Иван Петрович', 'Эндокринолог');

-- Получаем ID врача (для локальной разработки будет 1)
SET @doctor_id = 1;

-- Пациенты
INSERT INTO patients (full_name, birth_date, diabetes_type, doctor_id, phone, email) VALUES
('Петров Петр Сергеевич', '1985-03-15', 'type1', @doctor_id, '+79161234567', 'petrov@mail.ru'),
('Сидорова Мария Ивановна', '1978-07-22', 'type2', @doctor_id, '+79169876543', 'sidorova@mail.ru'),
('Кузнецов Алексей Николаевич', '1992-11-08', 'type1', @doctor_id, '+79165551234', 'kuznetsov@mail.ru');

-- Показатели глюкозы за последние 7 дней для первого пациента
INSERT INTO glucose_readings (patient_id, glucose_level, measurement_time, sensor_id) VALUES
-- Сегодня
(1, 5.2, DATE_SUB(NOW(), INTERVAL 2 HOUR), 'SENSOR001'),
(1, 6.8, DATE_SUB(NOW(), INTERVAL 6 HOUR), 'SENSOR001'),
(1, 4.5, DATE_SUB(NOW(), INTERVAL 10 HOUR), 'SENSOR001'),
-- Вчера
(1, 7.1, DATE_SUB(NOW(), INTERVAL 1 DAY), 'SENSOR001'),
(1, 5.9, DATE_SUB(NOW(), INTERVAL 1 DAY) - INTERVAL 4 HOUR, 'SENSOR001'),
(1, 11.2, DATE_SUB(NOW(), INTERVAL 1 DAY) - INTERVAL 8 HOUR, 'SENSOR001'),
-- 2 дня назад
(1, 4.8, DATE_SUB(NOW(), INTERVAL 2 DAY), 'SENSOR001'),
(1, 6.2, DATE_SUB(NOW(), INTERVAL 2 DAY) - INTERVAL 6 HOUR, 'SENSOR001'),
-- Для второго пациента
(2, 8.5, DATE_SUB(NOW(), INTERVAL 3 HOUR), 'SENSOR002'),
(2, 7.9, DATE_SUB(NOW(), INTERVAL 8 HOUR), 'SENSOR002'),
(2, 3.1, DATE_SUB(NOW(), INTERVAL 12 HOUR), 'SENSOR002'),
-- Для третьего пациента
(3, 6.5, DATE_SUB(NOW(), INTERVAL 1 HOUR), 'SENSOR003'),
(3, 5.8, DATE_SUB(NOW(), INTERVAL 5 HOUR), 'SENSOR003');

-- Дозы инсулина
INSERT INTO insulin_doses (patient_id, insulin_amount, insulin_type, administration_time) VALUES
(1, 8.0, 'Быстрого действия', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 12.0, 'Продленного действия', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 10.0, 'Быстрого действия', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 6.0, 'Быстрого действия', DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Оповещения (включая необработанные)
INSERT INTO alerts (patient_id, alert_type, glucose_value, alert_time, is_acknowledged, doctor_id) VALUES
(1, 'critical_high', 11.2, DATE_SUB(NOW(), INTERVAL 1 DAY) - INTERVAL 8 HOUR, FALSE, @doctor_id),
(2, 'critical_low', 3.1, DATE_SUB(NOW(), INTERVAL 12 HOUR), FALSE, @doctor_id),
(1, 'warning', 7.1, DATE_SUB(NOW(), INTERVAL 1 DAY), TRUE, @doctor_id);

-- Рекомендации
INSERT INTO recommendations (patient_id, doctor_id, recommendation_text, recommended_insulin_dose) VALUES
(1, @doctor_id, 'Увеличить дозу инсулина продленного действия. Контролировать уровень глюкозы после приема пищи.', 14.0),
(2, @doctor_id, 'Срочно принять 15г быстрых углеводов. Повторить измерение через 15 минут.', NULL);
