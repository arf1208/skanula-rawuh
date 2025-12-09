<?php
// backend/api/api.php - Endpoint Penerima Data dari ESP32
require_once '../config/database.php'; // Ambil koneksi DB

header('Content-Type: application/json');

// --- 1. VALIDASI INPUT ---
if (!isset($_GET['rfid_uid']) || empty($_GET['rfid_uid'])) {
    sendResponse("ERROR", "UID tidak diterima.");
}

$rfid_uid = $_GET['rfid_uid'];
$conn = connectDB();
$today = date('Y-m-d');
$current_time = date('H:i:s');
$jam_masuk_target = '07:00:00'; // Batas Waktu Tepat

// ----------------------------------------------------
// 2. LOGIKA HARI LIBUR & CHECKING
// ----------------------------------------------------
$dayOfWeek = date('N'); // 6=Sabtu, 7=Minggu

// Cek Hari Libur Weekend
if ($dayOfWeek == 6 || $dayOfWeek == 7) {
    sendResponse("INFO", "Hari ini weekend. Absensi non-aktif.");
}

// Cek Hari Libur Khusus
$stmt_libur = $conn->prepare("SELECT keterangan FROM hari_libur WHERE tanggal = ?");
$stmt_libur->bind_param("s", $today);
$stmt_libur->execute();
$stmt_libur->store_result();
if ($stmt_libur->num_rows > 0) {
    sendResponse("INFO", "Hari libur. Absensi non-aktif.");
}
$stmt_libur->close();

// ----------------------------------------------------
// 3. VERIFIKASI UID dan ABSENSI
// ----------------------------------------------------
$stmt_user = $conn->prepare("SELECT id, nama, role FROM users WHERE rfid_uid = ?");
$stmt_user->bind_param("s", $rfid_uid);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

if (!$user) {
    sendResponse("FAILED", "Kartu tidak terdaftar.");
}

$user_id = $user['id'];

// Cek apakah user sudah absen hari ini
$stmt_check = $conn->prepare("SELECT id, jam_masuk, jam_pulang FROM absensi WHERE user_id = ? AND tanggal = ?");
$stmt_check->bind_param("is", $user_id, $today);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$absensi_today = $result_check->fetch_assoc();
$stmt_check->close();

if (!$absensi_today) {
    // ABSEN MASUK
    $status_masuk = ($current_time > $jam_masuk_target) ? 'Terlambat' : 'Hadir';
    
    $stmt_insert = $conn->prepare("INSERT INTO absensi (user_id, tanggal, jam_masuk, status) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("isss", $user_id, $today, $current_time, $status_masuk);
    $stmt_insert->execute();
    $stmt_insert->close();
    
    triggerWhatsappApi($conn, $user_id, $user['nama'], 'MASUK', $current_time, $status_masuk);
    sendResponse("SUCCESS", $user['nama'] . ", Masuk: " . $status_masuk);

} else if ($absensi_today['jam_pulang'] == NULL && $absensi_today['jam_masuk'] != NULL) {
    // ABSEN PULANG
    $stmt_update = $conn->prepare("UPDATE absensi SET jam_pulang = ?, status = 'Pulang' WHERE id = ?");
    $stmt_update->bind_param("si", $current_time, $absensi_today['id']);
    $stmt_update->execute();
    $stmt_update->close();
    
    triggerWhatsappApi($conn, $user_id, $user['nama'], 'PULANG', $current_time, 'Pulang');
    sendResponse("SUCCESS", $user['nama'] . ", Absen Pulang Dicatat.");
    
} else {
    // SUDAH ABSEN MASUK DAN PULANG
    sendResponse("DUPLICATE", $user['nama'] . ", Selesai absen hari ini.");
}

$conn->close();

// --- FUNGSI HELPER ---
function sendResponse($status, $msg) {
    echo json_encode(["status" => $status, "msg" => $msg]);
    exit();
}

function triggerWhatsappApi($conn, $user_id, $nama, $tipe_absen, $waktu, $status) {
    // Logika Mendapatkan Nomor WA
    // Dapatkan nomor WA berdasarkan role dari biodata_siswa atau biodata_guru
    $stmt_wa = $conn->prepare("
        SELECT 
            CASE u.role 
                WHEN 'siswa' THEN bs.no_wa_ortu 
                WHEN 'guru' THEN bg.no_wa 
            END as target_wa, u.role
        FROM users u
        LEFT JOIN biodata_siswa bs ON u.id = bs.user_id
        LEFT JOIN biodata_guru bg ON u.id = bg.user_id
        WHERE u.id = ?
    ");
    $stmt_wa->bind_param("i", $user_id);
    $stmt_wa->execute();
    $data_wa = $stmt_wa->get_result()->fetch_assoc();
    $stmt_wa->close();

    $no_wa_target = $data_wa['target_wa'] ?? null;
    if (!$no_wa_target) return; 

    // Isi Pesan
    $pesan = ($tipe_absen == 'MASUK') 
        ? "🔔 [ABSEN MASUK SMK NU]\nNama: {$nama}\nWaktu: {$waktu}\nStatus: {$status}"
        : "🔔 [ABSEN PULANG SMK NU]\nNama: {$nama}\nWaktu: {$waktu}";

    // --- INTEGRASI WA BOT (ASUMSI SELF-HOSTED GATEWAY) ---
    $wa_gateway_url = "http://localhost:3000/send-message"; // GANTI DENGAN URL API WA GRATIS ANDA
    
    $payload = json_encode(['to' => $no_wa_target, 'message' => $pesan]);

    $ch = curl_init($wa_gateway_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch); // Eksekusi kirim pesan (tanpa menunggu respons)
    curl_close($ch);
}
?>
