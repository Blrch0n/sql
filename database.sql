CREATE DATABASE IF NOT EXISTS hospital_db;
USE hospital_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    released TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    specialization VARCHAR(100),
    phone VARCHAR(30),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE doctor_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    is_booked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (doctor_id, slot_date, slot_time),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    reason TEXT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Seed Data

-- Security Fix: Least-privilege MySQL user
CREATE USER IF NOT EXISTS 'hospital_app'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON hospital_db.* TO 'hospital_app'@'localhost';
FLUSH PRIVILEGES;

-- Indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_doctors_ids ON doctors(user_id, department_id);
CREATE INDEX idx_doctor_slots_datetime ON doctor_slots(doctor_id, slot_date, slot_time);
CREATE INDEX idx_appointments_lookup ON appointments(patient_id, doctor_id, appointment_date, appointment_time);

INSERT INTO users (full_name, email, password, role) VALUES 
('Админ', 'admin@medicare.mn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Жаргал', 'patient@medicare.mn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient'),
('Бат Эмч', 'doctor@medicare.mn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor');

INSERT INTO departments (name) VALUES 
('Дотрын тасаг'),
('Мэс заслын тасаг'),
('Шүдний тасаг');

INSERT INTO categories (name) VALUES 
('Туршилт'),
('Шинжилгээ'),
('Эмчилгээ');

INSERT INTO products (name, description, category, price, released) VALUES 
('Бүтээгдэхүүн 1', 'Тайлбар 1', 'Туршилт', 15000.00, 1),
('Бүтээгдэхүүн 2', 'Тайлбар 2', 'Шинжилгээ', 20000.00, 1),
('Бүтээгдэхүүн 3', 'Тайлбар 3', 'Эмчилгээ', 5000.00, 1),
('Нууц Бүтээгдэхүүн', 'Харагдахгүй тайлбар', 'Туршилт', 100000.00, 0);

INSERT INTO doctors (user_id, department_id, specialization, phone) VALUES 
(3, 1, 'Дотрын эмч', '99112233');

INSERT INTO doctor_slots (doctor_id, slot_date, slot_time, is_booked) VALUES 
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 0),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 0),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00:00', 0),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 0),
(1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:00:00', 0);
