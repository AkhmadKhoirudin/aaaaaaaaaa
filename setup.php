<?php
// File untuk setup database pertama kali
require_once 'config/database.php';

// Fungsi untuk mengecek apakah database sudah ada
function checkDatabaseExists($pdo) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM users LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk setup database
function setupDatabase($pdo) {
    try {
        // Baca file SQL
        $sql = file_get_contents('config/database.sql');
        
        // Eksekusi SQL
        $pdo->exec($sql);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Cek apakah database sudah ada
if (checkDatabaseExists($pdo)) {
    $message = 'Database sudah terinstall. Aplikasi siap digunakan.';
    $type = 'success';
} else {
    // Setup database
    if (setupDatabase($pdo)) {
        $message = 'Database berhasil diinstall. Silakan login dengan username: admin dan password: password';
        $type = 'success';
    } else {
        $message = 'Gagal menginstall database. Silakan cek koneksi database.';
        $type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup CBT - Aplikasi Ujian Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h2>Setup Aplikasi CBT</h2>
        <p class="text-muted">Aplikasi Ujian Online Computer Based Test</p>
        
        <?php if ($type == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-2x mb-3"></i><br>
                <?php echo $message; ?>
            </div>
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Login Sekarang
            </a>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
                <?php echo $message; ?>
            </div>
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="fas fa-redo"></i> Coba Lagi
            </button>
        <?php endif; ?>
        
        <hr class="my-4">
        
        <div class="text-start">
            <h6>Informasi Login Default:</h6>
            <ul class="list-unstyled">
                <li><strong>Admin:</strong> username: admin, password: password</li>
                <li><strong>Database:</strong> MySQL</li>
                <li><strong>Framework:</strong> Native PHP</li>
            </ul>
        </div>
        
        <hr>
        
        <small class="text-muted">
            &copy; 2024 Aplikasi CBT. Dikembangkan untuk SMK Negeri 1 Contoh.
        </small>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>