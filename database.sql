DROP DATABASE IF EXISTS hospital_db;
CREATE DATABASE hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    specialization VARCHAR(100),
    phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE doctor_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    is_booked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE(doctor_id, slot_date, slot_time)
);

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    released BOOLEAN DEFAULT 1
);

-- Indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_doctors_user_id ON doctors(user_id);
CREATE INDEX idx_doctors_department_id ON doctors(department_id);
CREATE INDEX idx_doctor_slots_datetime ON doctor_slots(doctor_id, slot_date, slot_time);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_lookup ON appointments(doctor_id, appointment_date, appointment_time);

-- Seed users
INSERT INTO users (full_name, email, password, role) VALUES 
('System Admin', 'admin@medicare.mn', '$2y$10$wE6CPE6B316hTYsB6P4J7.F8E1l8.JdE9y6w.Y7yE0z7y.Y.Y.Y.Y', 'admin'),
('Doctor Who', 'doctor@medicare.mn', '$2y$10$wE6CPE6B316hTYsB6P4J7.F8E1l8.JdE9y6w.Y7yE0z7y.Y.Y.Y.Y', 'doctor'),
('Patient Zero', 'patient@medicare.mn', '$2y$10$wE6CPE6B316hTYsB6P4J7.F8E1l8.JdE9y6w.Y7yE0z7y.Y.Y.Y.Y', 'patient');

-- Seed departments
INSERT INTO departments (name) VALUES ('Cardiology'), ('Neurology'), ('Pediatrics');

-- Seed doctors
INSERT INTO doctors (user_id, department_id, specialization, phone) VALUES (2, 1, 'General Cardiology', '99112233');

-- Seed slots
INSERT INTO doctor_slots (doctor_id, slot_date, slot_time, is_booked) VALUES 
(1, CURDATE() + INTERVAL 1 DAY, '10:00:00', 0),
(1, CURDATE() + INTERVAL 1 DAY, '11:00:00', 0),
(1, CURDATE() + INTERVAL 1 DAY, '13:00:00', 0),
(1, CURDATE() + INTERVAL 2 DAY, '10:00:00', 0),
(1, CURDATE() + INTERVAL 2 DAY, '14:00:00', 0);

-- Seed categories & products
INSERT INTO categories (name) VALUES ('Gifts'), ('Tech gifts'), ('Books');
INSERT INTO products (category, name, description, price, released) VALUES 
('Gifts', 'Flower Bouquet', 'Beautiful flowers', 25.00, 1),
('Tech gifts', 'Smart Watch', 'Fitness tracker', 150.00, 1),
('Books', 'Medical Encyclopedia', 'Thick book', 120.00, 1),
('Gifts', 'Нууц Бүтээгдэхүүн', 'Hidden Product', 999.00, 0);

-- User setup
CREATE USER IF NOT EXISTS 'hospital_app'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON hospital_db.* TO 'hospital_app'@'localhost';
FLUSH PRIVILEGES;
