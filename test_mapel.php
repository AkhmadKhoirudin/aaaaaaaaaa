<?php
// Test script untuk memverifikasi masalah tambah mata pelajaran

require_once 'config/database.php';
require_once 'includes/functions.php';

// Start session
session_start();

echo "<h2>Test Session</h2>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "Session user_id tidak ada<br>";
}

echo "<hr>";

echo "<h2>Test Database Connection</h2>";
try {
    $test = $pdo->query("SELECT 1");
    echo "Database connection: OK<br>";
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>Test Tabel Mata Pelajaran</h2>";
try {
    $result = $pdo->query("DESCRIBE mata_pelajaran");
    echo "Tabel mata_pelajaran ada<br>";
    echo "Struktur tabel:<br>";
    while ($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>Test Insert Mata Pelajaran</h2>";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_insert'])) {
    try {
        $kode_mapel = $_POST['kode_mapel'];
        $nama_mapel = $_POST['nama_mapel'];
        
        $stmt = $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel, nama_mapel) VALUES (?, ?)");
        $stmt->execute([$kode_mapel, $nama_mapel]);
        
        echo "<div style='color: green;'>Insert berhasil! ID: " . $pdo->lastInsertId() . "</div>";
    } catch (PDOException $e) {
        echo "<div style='color: red;'>Insert gagal: " . $e->getMessage() . "</div>";
    }
}

// Form test
?>
<form method="POST">
    <h3>Test Form Insert</h3>
    Kode Mapel: <input type="text" name="kode_mapel" required><br>
    Nama Mapel: <input type="text" name="nama_mapel" required><br>
    <button type="submit" name="test_insert">Test Insert</button>
</form>

<hr>

<h2>Data Mata Pelajaran Saat Ini</h2>
<?php
try {
    $mapel = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY kode_mapel")->fetchAll();
    if (count($mapel) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Kode</th><th>Nama</th></tr>";
        foreach ($mapel as $m) {
            echo "<tr>";
            echo "<td>" . $m['id'] . "</td>";
            echo "<td>" . htmlspecialchars($m['kode_mapel']) . "</td>";
            echo "<td>" . htmlspecialchars($m['nama_mapel']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Belum ada data mata pelajaran";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>