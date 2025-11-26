<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek session cetak data
if (!isset($_SESSION['cetak_data'])) {
    header("Location: cetak_kartu.php");
    exit();
}

$peserta = $_SESSION['cetak_data'];
unset($_SESSION['cetak_data']);

// Ambil data sekolah
$stmt = $pdo->query("SELECT * FROM sekolah LIMIT 1");
$sekolah = $stmt->fetch();

// Username sudah diambil dari query sebelumnya, password menggunakan NIS sebagai default
foreach ($peserta as &$p) {
    // Jika username belum ada, set username dari NIS
    if (!isset($p['username']) || empty($p['username'])) {
        $p['username'] = $p['nis'];
    }
    // Password default menggunakan NIS
    $p['password'] = $p['nis'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta Ujian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .kartu-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        
        .kartu {
            width: 350px;
            height: 200px;
            border: 2px solid #000;
            background: white;
            position: relative;
            page-break-inside: avoid;
            margin-bottom: 20px;
        }
        
        .kartu-header {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }
        
        .kartu-header h6 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .kartu-body {
            padding: 10px;
            font-size: 12px;
        }
        
        .kartu-row {
            display: flex;
            justify-content: space-between;
        }
        
        .kartu-info {
            flex: 1;
        }
        
        .kartu-info p {
            margin: 2px 0;
        }
        
        .kartu-foto {
            width: 80px;
            height: 100px;
            border: 1px solid #ccc;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        
        .kartu-token {
            text-align: center;
            margin-top: 10px;
            padding: 5px;
            background: #e9ecef;
            border-radius: 3px;
        }
        
        .kartu-login {
            margin-top: 5px;
            padding: 5px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .kartu-login strong {
            color: #155724;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                background: white;
            }
            .kartu {
                margin-bottom: 10px;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <h2>Kartu Peserta Ujian</h2>
        <p><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'Sekolah') ?></p>
        <hr>
    </div>
    
    <div class="kartu-container">
        <?php 
        $count = 0;
        foreach ($peserta as $p): 
            $count++;
        ?>
            <div class="kartu">
                <div class="kartu-header">
                    <h6>KARTU PESERTA UJIAN</h6>
                </div>
                <div class="kartu-body">
                    <div class="kartu-row">
                        <div class="kartu-info">
                            <p><strong>Nama:</strong> <?= htmlspecialchars($p['nama']) ?></p>
                            <p><strong>NIS:</strong> <?= htmlspecialchars($p['nis']) ?></p>
                            <p><strong>NISN:</strong> <?= htmlspecialchars($p['nisn']) ?></p>
                            <p><strong>Kelas:</strong> <?= htmlspecialchars($p['nama_kelas']) ?></p>
                            <p><strong>Ujian:</strong> <?= htmlspecialchars($p['ujian_nama']) ?></p>
                            <p><strong>Ruang:</strong> <?= htmlspecialchars($p['kode_ruang'] . ' - ' . $p['nama_ruang']) ?></p>
                        </div>
                        <div class="kartu-foto">
                            <small>Foto</small>
                        </div>
                    </div>
                    <div class="kartu-token">
                        <strong>Token Ujian:</strong> <?= htmlspecialchars($p['token_ujian']) ?>
                    </div>
                    <div class="kartu-login">
                        <strong>Login:</strong> Username: <?= htmlspecialchars($p['username']) ?> | Password: <?= htmlspecialchars($p['password']) ?>
                    </div>
                </div>
            </div>
            
            <?php if ($count % 4 == 0): ?>
                <div class="page-break"></div>
            <?php endif; ?>
            
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Cetak Kartu
        </button>
        <button onclick="window.location.href='cetak_kartu.php'" style="padding: 10px 20px; font-size: 16px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Kembali
        </button>
    </div>
    
    <script>
        // Auto print jika diinginkan
        // window.print();
    </script>
</body>
</html>