<?php
// backend/admin/rekap_absensi.php - Rekapitulasi Absensi, Filter, dan Export
require_once '../config/database.php';
checkLogin();

$conn = connectDB();
$data_absensi = [];
$filter_date = $_GET['filter_date'] ?? date('Y-m-d');
$filter_role = $_GET['filter_role'] ?? 'all'; // 'all', 'siswa', 'guru'
$users_list = []; // Untuk dropdown filter

// Ambil semua pengguna untuk dropdown filter
$users_result = $conn->query("SELECT id, nama, role FROM users ORDER BY nama ASC");
while ($row = $users_result->fetch_assoc()) {
    $users_list[] = $row;
}

// --- LOGIKA QUERY DATA ABSENSI DENGAN FILTER ---
$sql = "
    SELECT 
        a.tanggal, a.jam_masuk, a.jam_pulang, a.status, 
        u.nama, u.role, u.rfid_uid, 
        COALESCE(bs.kelas, '-') as detail
    FROM absensi a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN biodata_siswa bs ON u.id = bs.user_id AND u.role = 'siswa'
    ORDER BY a.tanggal DESC, a.jam_masuk ASC
";

$where_clauses = ["1=1"];
$params = [];
$types = "";

// Filter berdasarkan Tanggal
if (!empty($filter_date)) {
    $where_clauses[] = "a.tanggal = ?";
    $params[] = $filter_date;
    $types .= "s";
}

// Filter berdasarkan Role (Siswa/Guru)
if ($filter_role !== 'all') {
    $where_clauses[] = "u.role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

$final_sql = "SELECT 
        a.tanggal, a.jam_masuk, a.jam_pulang, a.status, 
        u.nama, u.role, u.rfid_uid, 
        COALESCE(bs.kelas, bg.nip) as detail
    FROM absensi a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN biodata_siswa bs ON u.id = bs.user_id
    LEFT JOIN biodata_guru bg ON u.id = bg.user_id
    WHERE " . implode(" AND ", $where_clauses) . "
    ORDER BY a.tanggal DESC, a.jam_masuk ASC";

// Siapkan dan eksekusi statement
if (!empty($params)) {
    $stmt = $conn->prepare($final_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_absensi = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Jika tidak ada filter, jalankan query tanpa prepared statement
    $result = $conn->query(str_replace("WHERE 1=1 AND", "WHERE", $final_sql));
    $data_absensi = $result->fetch_all(MYSQLI_ASSOC);
}

// ----------------------------------------------------
// 2. LOGIKA EXPORT KE EXCEL
// ----------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] == 'true') {
    $filename = "Rekap_Absensi_SMK_NU_" . date('Ymd_His') . ".xls";

    // Set header untuk memberitahu browser bahwa ini adalah file excel
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);

    // Output data tabel sebagai HTML
    echo "<style>table, th, td {border: 1px solid black; border-collapse: collapse;}</style>";
    echo "<h2>Rekap Absensi SMK NU Lamongan</h2>";
    echo "<p>Filter Tanggal: " . htmlspecialchars($filter_date) . "</p>";
    echo "<table border='1'>";
    
    // Header Tabel
    echo "<tr>
            <th>No.</th>
            <th>Tanggal</th>
            <th>Nama</th>
            <th>Role</th>
            <th>Kelas/NIP</th>
            <th>UID RFID</th>
            <th>Jam Masuk</th>
            <th>Jam Pulang</th>
            <th>Status</th>
          </tr>";

    // Isi Tabel
    $no = 1;
    foreach ($data_absensi as $data) {
        $detail_label = ($data['role'] === 'siswa') ? 'Kelas' : 'NIP';
        echo "<tr>";
        echo "<td>" . $no++ . "</td>";
        echo "<td>" . htmlspecialchars($data['tanggal']) . "</td>";
        echo "<td>" . htmlspecialchars($data['nama']) . "</td>";
        echo "<td>" . ucfirst(htmlspecialchars($data['role'])) . "</td>";
        echo "<td>" . htmlspecialchars($data['detail']) . "</td>";
        echo "<td>" . htmlspecialchars($data['rfid_uid']) . "</td>";
        echo "<td>" . htmlspecialchars($data['jam_masuk']) . "</td>";
        echo "<td>" . htmlspecialchars($data['jam_pulang'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($data['status']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
    
    $conn->close();
    exit(); // Hentikan eksekusi script setelah mengirim file
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi - SMK NU LAMONGAN</title>
    <link rel="stylesheet" href="assets/css/style.css"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div id="wrapper">
        <?php include('includes/sidebar.php'); ?>
        
        <div id="content">
            <?php include('includes/header.php'); ?>

            <h2><i class="fas fa-clipboard-list"></i> Rekapitulasi Absensi</h2>
            <hr>

            <form method="GET" action="rekap_absensi.php" class="filter-form">
                <label for="filter_date">Filter Tanggal:</label>
                <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" required>

                <label for="filter_role">Filter Role:</label>
                <select name="filter_role" id="filter_role">
                    <option value="all" <?php echo $filter_role == 'all' ? 'selected' : ''; ?>>Semua</option>
                    <option value="siswa" <?php echo $filter_role == 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                    <option value="guru" <?php echo $filter_role == 'guru' ? 'selected' : ''; ?>>Guru</option>
                </select>

                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
                
                <a href="rekap_absensi.php?export=true&filter_date=<?php echo htmlspecialchars($filter_date); ?>&filter_role=<?php echo htmlspecialchars($filter_role); ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="fas fa-file-excel"></i> Export ke Excel
                </a>
            </form>
            
            <div class="data-table-container">
                <h3 style="margin-top: 20px;">Data Absensi pada Tanggal: <?php echo date('d F Y', strtotime($filter_date)); ?></h3>
                <table class="data-table" id="absensi-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>Kelas/NIP</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data_absensi) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($data_absensi as $data): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($data['tanggal']); ?></td>
                                <td><?php echo htmlspecialchars($data['nama']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($data['role'])); ?></td>
                                <td><?php echo htmlspecialchars($data['detail']); ?></td>
                                <td><?php echo htmlspecialchars($data['jam_masuk']); ?></td>
                                <td><?php echo htmlspecialchars($data['jam_pulang'] ?? '-'); ?></td>
                                <td class="<?php echo strtolower($data['status']); ?>-status"><?php echo htmlspecialchars($data['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center;">Tidak ada data absensi pada filter ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    
    <script src="assets/js/scripts.js"></script>
</body>
</html>
