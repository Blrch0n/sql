USE hospital_db;
-- Seed users
INSERT IGNORE INTO users (full_name, email, password, role) VALUES 
('System Admin', 'admin@medicare.mn', '$2y$12$mL0tbVUpT6oK4jn0xT0CFe4oKWJCMJASoNk9nDieZiCeKCXIMuxsK', 'admin'),
('Doctor Who', 'doctor@medicare.mn', '$2y$12$mL0tbVUpT6oK4jn0xT0CFe4oKWJCMJASoNk9nDieZiCeKCXIMuxsK', 'doctor'),
('Patient Zero', 'patient@medicare.mn', '$2y$12$mL0tbVUpT6oK4jn0xT0CFe4oKWJCMJASoNk9nDieZiCeKCXIMuxsK', 'patient');

-- Seed departments
INSERT IGNORE INTO departments (name) VALUES ('Cardiology'), ('Neurology'), ('Pediatrics');

-- Seed doctors
INSERT IGNORE INTO doctors (user_id, department_id, specialization, phone) VALUES (2, 1, 'General Cardiology', '99112233');

-- Seed slots
INSERT IGNORE INTO doctor_slots (doctor_id, slot_date, slot_time, is_booked) VALUES 
(1, CURDATE() + INTERVAL 1 DAY, '10:00:00', 0),
(1, CURDATE() + INTERVAL 1 DAY, '11:00:00', 0),
(1, CURDATE() + INTERVAL 1 DAY, '13:00:00', 0),
(1, CURDATE() + INTERVAL 2 DAY, '10:00:00', 0),
(1, CURDATE() + INTERVAL 2 DAY, '14:00:00', 0);

-- Seed categories & products
INSERT IGNORE INTO categories (name) VALUES ('Gifts'), ('Tech gifts'), ('Books');
INSERT IGNORE INTO products (category, name, description, price, released) VALUES 
('Gifts', 'Flower Bouquet', 'Beautiful flowers', 25.00, 1),
('Tech gifts', 'Smart Watch', 'Fitness tracker', 150.00, 1),
('Books', 'Medical Encyclopedia', 'Thick book', 120.00, 1),
('Gifts', 'Нууц Бүтээгдэхүүн', 'Hidden Product', 999.00, 0);
