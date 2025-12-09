<?php
// backend/index.php - Halaman Login Admin

// Path relatif ke config/database.php (keluar satu folder, lalu masuk ke config)
require_once 'config/database.php'; 

$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $conn = connectDB();
    
    // 1. Ambil data admin dari database berdasarkan username
    $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        // 2. Verifikasi Password (menggunakan MD5 karena di DB kita pakai MD5)
        // Peringatan: Dalam proyek nyata, gunakan password_verify()
        if (MD5($password) === $admin['password']) {
            
            // 3. Login Berhasil: Buat Session
            $_SESSION['is_admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            
            // 4. Redirect ke Dashboard Admin
            header("Location: admin/index.php");
            exit();
        } else {
            $error_msg = "Password salah! Cek kembali.";
        }
    } else {
        $error_msg = "Username tidak ditemukan!";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SMK NU LAMONGAN</title>
    <link rel="stylesheet" href="admin/assets/css/style.css"> 
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Sistem Absensi RFID</h2>
        <h1>SMK NU LAMONGAN</h1>
        
        <?php if ($error_msg): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error_msg); ?></p>
        <?php endif; ?>
        
        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="btn-primary">LOGIN</button>
        </form>
        <p style="margin-top: 20px; text-align: center; font-size: 0.9em; color: #888;">Default Login: admin / admin123</p>
    </div>
</body>
</html>
