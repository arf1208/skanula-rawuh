-- database/database.sql
-- Digunakan untuk membuat database dan tabel

CREATE DATABASE IF NOT EXISTS db_absensi_smknu;
USE db_absensi_smknu;

-- 1. Tabel Admin (Password Default: 'smknuadmin' - disimpan dalam hash MD5)
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL -- Simpan Hash Password
);
-- Password: 'smknuadmin' (MD5 hash)
INSERT INTO admin (username, password) VALUES ('smknuadmin', MD5('smknuadmin')); 

-- 2. Tabel Users (Siswa/Guru)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_uid VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('siswa', 'guru') NOT NULL
);

-- 3. Tabel Detail Siswa
CREATE TABLE biodata_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    kelas VARCHAR(10),
    no_wa_ortu VARCHAR(15),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- 4. Tabel Detail Guru
CREATE TABLE biodata_guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    nip VARCHAR(20),
    no_wa VARCHAR(15),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Tabel Absensi
CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_pulang TIME,
    status ENUM('Hadir', 'Terlambat', 'Pulang') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Tabel Hari Libur
CREATE TABLE hari_libur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE UNIQUE NOT NULL,
    keterangan VARCHAR(100)
);

-- Contoh Data Tes
INSERT INTO users (rfid_uid, nama, role) VALUES ('A1B2C3D4', 'Arief rahman', 'siswa');
INSERT INTO biodata_siswa (user_id, kelas, no_wa_ortu) VALUES (1, 'XI TKJ 1', '085812682418');
INSERT INTO users (rfid_uid, nama, role) VALUES ('E5F6G7H8', 'Paidi Guru ', 'guru');
INSERT INTO biodata_guru (user_id, nip, no_wa) VALUES (2, '12345678', '085812682418');
