-- Migration: 2026_05_14_fix_patient_features
-- Run this against hospital_db if your schema was created BEFORE the phone/updated_at/doctor_reviews additions.
-- Safe to run multiple times (uses IF NOT EXISTS / ADD COLUMN IF NOT EXISTS pattern).

USE hospital_db;

-- Add phone column to users (for patient profile)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(150) NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read (user_id, is_read, created_at)
);

-- Doctor reviews table
CREATE TABLE IF NOT EXISTS doctor_reviews (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    patient_id     INT NOT NULL,
    doctor_id      INT NOT NULL,
    rating         TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment        TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (patient_id)     REFERENCES users(id),
    FOREIGN KEY (doctor_id)      REFERENCES doctors(id),
    INDEX idx_reviews_doctor (doctor_id, rating)
);
