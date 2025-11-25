<?php
require_once 'config/database.php';

// Buat user admin untuk testing
try {
    // Cek apakah user admin sudah ada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $exists = $stmt->fetchColumn();
    
    if (!$exists) {
        // Hash password
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Insert user admin
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $password, 'Administrator', 'admin', 'aktif']);
        
        echo "✓ User admin berhasil dibuat\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "✓ User admin sudah ada\n";
    }
    
    // Cek apakah user operator sudah ada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['operator']);
    $exists = $stmt->fetchColumn();
    
    if (!$exists) {
        // Hash password
        $password = password_hash('operator123', PASSWORD_DEFAULT);
        
        // Insert user operator
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['operator', $password, 'Operator', 'operator', 'aktif']);
        
        echo "✓ User operator berhasil dibuat\n";
        echo "Username: operator\n";
        echo "Password: operator123\n";
    } else {
        echo "✓ User operator sudah ada\n";
    }
    
    echo "\nSilakan login dengan user admin untuk menguji sidebar.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>