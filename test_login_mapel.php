<?php
// Skrip untuk test login dan akses mapel.php

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Login otomatis sebagai admin untuk test
if (!isset($_SESSION['user_id'])) {
    // Cek user admin di database
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
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "❌ User admin tidak ditemukan di database";
        echo "</div>";
        exit;
    }
}

// Test akses mapel.php
echo "<h2>Test Akses mapel.php</h2>";

// Cek role
if (hasRole(['admin', 'operator'])) {
    echo "<div style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
    echo "✅ Akses diizinkan untuk role: " . $_SESSION['role'];
    echo "</div>";
    
    // Test form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_mapel'])) {
        echo "<h3>Test Form Submission</h3>";
        
        $kode_mapel = $_POST['kode_mapel'] ?? '';
        $nama_mapel = $_POST['nama_mapel'] ?? '';
        
        if (empty($kode_mapel) || empty($nama_mapel)) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 10px 0;'>";
            echo "⚠️ Form validation: Kode dan nama mata pelajaran harus diisi";
            echo "</div>";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel, nama_mapel) VALUES (?, ?)");
                $stmt->execute([$kode_mapel, $nama_mapel]);
                
                echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "✅ Mata pelajaran berhasil ditambahkan! ID: " . $pdo->lastInsertId();
                echo "</div>";
            } catch (PDOException $e) {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "❌ Error: " . $e->getMessage();
                echo "</div>";
            }
        }
    }
    
    // Tampilkan form test
    ?>
    <h3>Test Form Tambah Mata Pelajaran</h3>
    <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 5px; max-width: 400px;">
        <div style="margin-bottom: 15px;">
            <label>Kode Mapel:</label><br>
            <input type="text" name="kode_mapel" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Nama Mata Pelajaran:</label><br>
            <input type="text" name="nama_mapel" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <button type="submit" name="tambah_mapel" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            Test Tambah Mapel
        </button>
    </form>
    
    <?php
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "❌ Akses ditolak. Role Anda: " . $_SESSION['role'];
    echo "</div>";
}

// Tampilkan data mata pelajaran
echo "<h3>Data Mata Pelajaran Saat Ini</h3>";
try {
    $mapel = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY kode_mapel")->fetchAll();
    if (count($mapel) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; max-width: 600px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Kode</th><th>Nama Mata Pelajaran</th></tr>";
        foreach ($mapel as $m) {
            echo "<tr>";
            echo "<td>" . $m['id'] . "</td>";
            echo "<td>" . htmlspecialchars($m['kode_mapel']) . "</td>";
            echo "<td>" . htmlspecialchars($m['nama_mapel']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Belum ada data mata pelajaran</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/mapel.php' target='_blank'>Buka Halaman mapel.php (akan redirect ke login jika session tidak aktif)</a></p>";
?>