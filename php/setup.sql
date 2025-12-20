-- Database Setup for Seminar KP

-- Create Database if it doesn't exist
CREATE DATABASE IF NOT EXISTS seminar_kp;
USE seminar_kp;

-- 1. Table Users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nim VARCHAR(20) UNIQUE,
    angkatan CHAR(4),
    jurusan VARCHAR(50),
    role ENUM('student', 'admin', 'dosen') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table Seminars
CREATE TABLE IF NOT EXISTS seminars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL, -- Null if added by admin directly
    title VARCHAR(255) NOT NULL,
    speaker VARCHAR(100) NOT NULL,
    dosen VARCHAR(100),
    date DATE NOT NULL,
    time_start TIME,
    time_end TIME,
    location VARCHAR(100),
    quota_current INT DEFAULT 0,
    quota_max INT DEFAULT 0,
    image VARCHAR(255),
    documents TEXT,
    status ENUM('pending', 'active', 'full', 'finished') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. Table Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 4. Table Registrations
CREATE TABLE IF NOT EXISTS registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    seminar_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'attended', 'pending_verification') DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    proof_file VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (seminar_id) REFERENCES seminars(id)
);

-- Data Dummy
-- Clear existing data first to avoid duplicate key errors
DELETE FROM registrations;
DELETE FROM seminars;
ALTER TABLE seminars AUTO_INCREMENT = 1;

INSERT INTO seminars (id, title, speaker, dosen, date, time_start, time_end, location, quota_current, quota_max, image, status) VALUES
(1, 'IMPLEMENTASI TAMPILAN WEBSITE COMPANY PROFILE', 'Cut Alzeena Rency Fadania', 'Bapak Emha', '2025-11-28', '09:00:00', '11:00:00', 'Gedung F 101', 15, 20, 'assets/2.jpg', 'active'),
(2, 'IMPLEMENTASI TAMPILAN WEBSITE E-COMMERCE', 'Mellysa Ayu Wulan Sari', 'Bapak Emha', '2025-11-29', '10:00:00', '12:00:00', 'Gedung F 102', 20, 20, 'assets/3.jpg', 'full'),
(3, 'SISTEM INFORMASI MANAJEMEN ASET SEKOLAH', 'Edgina Syafa Ayu W.', 'Bapak Emha', '2025-11-28', '09:00:00', '11:00:00', 'Gedung F 101', 10, 20, 'assets/4.jpg', 'active'),
(4, 'RANCANG BANGUN APLIKASI KASIR BERBASIS WEB', 'Budi Santoso', 'Ibu Siti', '2025-12-10', '10:00:00', '12:00:00', 'Gedung H 204', 18, 20, 'assets/5.jpg', 'active'),
(5, 'ANALISIS UI/UX APLIKASI MOBILE BANKING', 'Siti Aminah', 'Bapak Joko', '2026-01-05', '13:00:00', '15:00:00', 'Gedung D 301', 5, 25, 'assets/6.webp', 'active'),
(6, 'PENGEMBANGAN GAME EDUKASI ANAK USIA DINI', 'Ahmad Rizki', 'Ibu Linda', '2026-01-12', '08:00:00', '10:00:00', 'Lab Komputer 1', 25, 25, 'assets/3.jpg', 'full'),
(7, 'IMPLEMENTASI IOT UNTUK SMART HOME', 'Dewi Lestari', 'Bapak Cahyo', '2026-01-15', '14:00:00', '16:00:00', 'Lab IoT', 12, 30, 'assets/4.jpg', 'active'),
(8, 'SISTEM PAKAR DIAGNOSA PENYAKIT TANAMAN', 'Eko Prasetyo', 'Bapak Emha', '2026-01-20', '09:00:00', '11:00:00', 'Gedung F 105', 8, 20, 'assets/5.jpg', 'active'),
(9, 'AUDIT SISTEM INFORMASI PERUSAHAAN RETAIL', 'Fajar Nugroho', 'Ibu Rina', '2025-12-01', '10:00:00', '12:00:00', 'Ruang Sidang', 20, 20, 'assets/6.webp', 'finished'),
(10, 'VISUALISASI DATA KEPENDUDUKAN KOTA SEMARANG', 'Gita Pertiwi', 'Bapak Budi', '2026-02-01', '13:00:00', '15:00:00', 'Gedung H 303', 2, 15, 'assets/4.jpg', 'active');
