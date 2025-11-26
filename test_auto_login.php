<?php
// Auto login dan redirect ke mapel.php untuk test

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Login otomatis sebagai admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
$stmt->execute(['admin']);
$user = $stmt->fetch();

if ($user) {
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
    
    echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
    echo "✅ Login berhasil sebagai: " . $user['username'] . " (Role: " . $user['role'] . ")";
    echo "</div>";
    
    echo "<p>Redirecting ke halaman mapel.php dalam 2 detik...</p>";
    
    // Redirect ke mapel.php
    echo "<script>
        setTimeout(function() {
            window.location.href = 'admin/mapel.php';
        }, 2000);
    </script>";
    
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "❌ User admin tidak ditemukan di database";
    echo "</div>";
}
?>