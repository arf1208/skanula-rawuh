<?php
// backend/admin/data_users.php - Manajemen Data Guru dan Siswa (CRUD)
require_once '../config/database.php';
checkLogin();

$conn = connectDB();
$message = '';
$message_type = '';

// --- FUNGSI HELPER ---

// Fungsi untuk mendapatkan data pengguna secara lengkap (join dengan biodata)
function getAllUsers($conn) {
    $sql = "
        SELECT 
            u.id, u.rfid_uid, u.nama, u.role, 
            COALESCE(bs.kelas, '-') as kelas, 
            COALESCE(bs.no_wa_ortu, bg.no_wa) as no_wa
        FROM users u
        LEFT JOIN biodata_siswa bs ON u.id = bs.user_id AND u.role = 'siswa'
        LEFT JOIN biodata_guru bg ON u.id = bg.user_id AND u.role = 'guru'
        ORDER BY u.id DESC
    ";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// --- LOGIKA CRUD ---

// 1. DELETE (Hapus Data)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Transaksi: Hapus dari tabel biodata_siswa/guru (dihandle ON DELETE CASCADE), lalu users.
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $message = "Data pengguna berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus data: " . $conn->error;
        $message_type = 'error';
    }
    $stmt->close();
    
    // Redirect untuk menghindari pengiriman ulang form
    header("Location: data_users.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// 2. CREATE (Tambah Data Baru)
if (isset($_POST['add_user'])) {
    $rfid_uid = strtoupper(trim($_POST['rfid_uid']));
    $nama = trim($_POST['nama']);
    $role = $_POST['role'];
    $kelas = $_POST['kelas'] ?? null;
    $no_wa = $_POST['no_wa'] ?? null; // Bisa no_wa_ortu atau no_wa guru

    if (empty($rfid_uid) || empty($nama) || empty($role)) {
        $message = "Semua kolom wajib diisi!";
        $message_type = 'error';
    } else {
        $conn->begin_transaction();
        try {
            // Insert ke tabel users
            $stmt = $conn->prepare("INSERT INTO users (rfid_uid, nama, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $rfid_uid, $nama, $role);
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt->close();

            // Insert ke tabel biodata
            if ($role === 'siswa') {
                $stmt = $conn->prepare("INSERT INTO biodata_siswa (user_id, kelas, no_wa_ortu) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $kelas, $no_wa);
            } else { // guru
                $stmt = $conn->prepare("INSERT INTO biodata_guru (user_id, nip, no_wa) VALUES (?, ?, ?)");
                // Kita gunakan kolom NIP sebagai placeholder di sini, sesuaikan jika ada form NIP terpisah
                $stmt->bind_param("iss", $user_id, $kelas, $no_wa); // Kelas diganti NIP jika perlu
            }
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = "Data pengguna berhasil ditambahkan!";
            $message_type = 'success';
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($e->getCode() == 1062) { // Kode error duplicate entry
                $message = "GAGAL: RFID UID sudah terdaftar. Gunakan UID lain.";
            } else {
                $message = "Gagal menambahkan data: " . $e->getMessage();
            }
            $message_type = 'error';
        }
    }
}


// --- AMBIL DATA UNTUK DITAMPILKAN ---
$users_data = getAllUsers($conn);

// Ambil pesan dari URL setelah redirect
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['type'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pengguna - SMK NU LAMONGAN</title>
