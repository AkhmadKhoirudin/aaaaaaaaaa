<?php
// Include database configuration
require_once 'config/database.php';

echo "=== VERIFIKASI SKEMA DATABASE ===\n\n";

try {
    // Cek koneksi database
    echo "Koneksi database berhasil.\n\n";
    
    // Fungsi untuk menampilkan struktur tabel
    function showTableSchema($pdo, $tableName) {
        echo "=== STRUKTUR TABEL: $tableName ===\n";
        
        // Query untuk mendapatkan informasi kolom
        $stmt = $pdo->prepare("DESCRIBE $tableName");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($columns)) {
            echo "Tabel '$tableName' tidak ditemukan.\n\n";
            return;
        }
        
        // Tampilkan informasi kolom
        echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . str_pad("Default", 15) . "Extra\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($columns as $column) {
            echo str_pad($column['Field'], 20) . 
                 str_pad($column['Type'], 20) . 
                 str_pad($column['Null'], 10) . 
                 str_pad($column['Key'], 10) . 
                 str_pad($column['Default'] ?? 'NULL', 15) . 
                 $column['Extra'] . "\n";
        }
        
        echo "\n";
        
        // Cek foreign key constraints
        $stmt = $pdo->prepare("
            SELECT 
                COLUMN_NAME, 
                CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$tableName]);
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($foreignKeys)) {
            echo "Foreign Key Constraints:\n";
            foreach ($foreignKeys as $fk) {
                echo "- {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            }
            echo "\n";
        }
    }
    
    // Cek apakah tabel ada
    $tables = ['bank_soal', 'users'];
    
    foreach ($tables as $table) {
        showTableSchema($pdo, $table);
    }
    
    // Verifikasi kolom spesifik
    echo "=== VERIFIKASI KOLOM SPESIFIK ===\n";
    
    // Cek kolom guru_id di tabel bank_soal
    $stmt = $pdo->prepare("SELECT COUNT(*) as exists FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'bank_soal' 
                          AND COLUMN_NAME = 'guru_id'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Kolom 'guru_id' di tabel 'bank_soal': " . ($result['exists'] ? "ADA" : "TIDAK ADA") . "\n";
    
    // Cek kolom id di tabel users
    $stmt = $pdo->prepare("SELECT COUNT(*) as exists FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'users' 
                          AND COLUMN_NAME = 'id'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Kolom 'id' di tabel 'users': " . ($result['exists'] ? "ADA" : "TIDAK ADA") . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>