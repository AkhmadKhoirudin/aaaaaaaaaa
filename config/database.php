<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'cbt_online');
define('DB_USER', 'root');
define('DB_PASS', '');

// Koneksi ke MySQL
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk mengecek koneksi database
function checkDatabaseConnection() {
    try {
        $test_pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk backup database
function backupDatabase($tables = '*') {
    global $pdo;
    
    $return = "";
    
    if ($tables == '*') {
        $tables = array();
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }
    
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT * FROM $table");
        $num_fields = $result->columnCount();
        
        $return .= "DROP TABLE IF EXISTS $table;";
        $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    
    $backup_file = 'uploads/backup/backup_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($backup_file, $return);
    
    return $backup_file;
}
?>