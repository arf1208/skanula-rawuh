<?php
// backend/config/database.php
// Konfigurasi Koneksi Database MySQL
session_start(); // Mulai sesi untuk fitur login

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Password Laragon default (kosong)
define('DB_NAME', 'db_absensi_smknu');

// Fungsi Koneksi
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // Hentikan eksekusi jika koneksi gagal
        die("Koneksi Database Gagal: " . $conn->connect_error);
    }
    return $conn;
}

// Fungsi Pengecekan Login
function checkLogin() {
    if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
        header("Location: /absensi-rfid-smknu/index.php"); // Ganti dengan path folder Anda
        exit();
    }
}
?>
