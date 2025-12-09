<?php
// backend/admin/index.php - Dashboard Utama Admin
// Pastikan path ke database.php sudah benar relatif dari subfolder admin
require_once '../config/database.php'; 
checkLogin(); // Panggil fungsi untuk memastikan admin sudah login

$conn = connectDB();
$username = $_SESSION['username'];
$today = date('Y-m-d');
$total_users = 0;
$total_hadir = 0;
$total_terlambat = 0;

// --- 1. Ambil Statistik Dasar ---

// Total Pengguna (Siswa + Guru)
$stmt = $conn->prepare("SELECT COUNT(id) AS total FROM users");
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total Hadir Hari Ini
$stmt = $conn->prepare("SELECT COUNT(id) AS total FROM absensi WHERE tanggal = ? AND status IN ('Hadir', 'Terlambat', 'Pulang')");
$stmt->bind_param("s", $today);
$stmt->execute();
$total_hadir = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total Terlambat Hari Ini
$stmt = $conn->prepare("SELECT COUNT(id) AS total FROM absensi WHERE tanggal = ? AND status = 'Terlambat'");
$stmt->bind_param("s", $today);
$stmt->execute();
$total_terlambat = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - SMK NU LAMONGAN</title>
    <link rel="stylesheet" href="assets/css/style.css"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div id="wrapper">
        <div id="sidebar">
            <div class="sidebar-header">
                <h3>SMK NU Admin</h3>
            </div>
            <ul class="list-unstyled components">
                <li class="active"><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="data_users.php"><i class="fas fa-users"></i> Data Pengguna</a></li>
                <li><a href="rekap_absensi.php"><i class="fas fa-clipboard-list"></i> Rekap Absensi</a></li>
                <li><a href="hari_libur.php"><i class="fas fa-calendar-alt"></i> Hari Libur</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div id="content">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <span class="welcome-text">Selamat Datang, **<?php echo htmlspecialchars($username); ?>**!</span>
                </div>
            </nav>

            <h2>Dashboard Absensi Hari Ini (<?php echo date('d M Y'); ?>)</h2>
            <hr>

            <div class="row card-container">
                <div class="card">
                    <div class="card-icon bg-primary"><i class="fas fa-users"></i></div>
                    <div class="card-body">
                        <h3>Total Pengguna</h3>
                        <p class="card-value"><?php echo $total_users; ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                    <div class="card-body">
                        <h3>Total Hadir</h3>
                        <p class="card-value"><?php echo $total_hadir; ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon bg-danger"><i class="fas fa-clock"></i></div>
                    <div class="card-body">
                        <h3>Total Terlambat</h3>
                        <p class="card-value"><?php echo $total_terlambat; ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="card-body">
                        <h3>Belum Absen</h3>
                        <?php 
                            $belum_absen = $total_users - $total_hadir; 
                        ?>
                        <p class="card-value"><?php echo max(0, $belum_absen); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="main-content-area">
                <h3>Aktivitas Absensi Terbaru</h3>
                <p>Tabel absensi terbaru akan ditampilkan di sini (Perlu query tambahan).</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/scripts.js"></script>
    <script>
        // JS untuk toggle sidebar (opsional untuk mobile view)
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });
    </script>
</body>
</html>
