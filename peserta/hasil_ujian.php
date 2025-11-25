<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['peserta_id'])) {
    header("Location: ../login.php");
    exit();
}

$sesi_id = $_GET['sesi_id'] ?? 0;
$peserta_id = $_SESSION['peserta_id'];

if ($sesi_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Ambil data hasil ujian
$stmt = $pdo->prepare("SELECT su.*, u.nama as ujian_nama, p.nama as paket_nama, ps.nama as peserta_nama, k.nama as kelas_nama 
                      FROM sesi_ujian su 
                      JOIN ujian u ON su.ujian_id = u.id 
                      JOIN paket_soal p ON u.paket_soal_id = p.id 
                      JOIN peserta ps ON su.peserta_id = ps.id 
                      JOIN kelas k ON ps.kelas_id = k.id 
                      WHERE su.id = ? AND su.peserta_id = ?");
$stmt->execute([$sesi_id, $peserta_id]);
$hasil = $stmt->fetch();

if (!$hasil) {
    header("Location: dashboard.php");
    exit();
}

// Hitung statistik
$total_soal = $hasil['benar'] + $hasil['salah'] + $hasil['kosong'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian - <?= htmlspecialchars($hasil['ujian_nama']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/peserta.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .result-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            margin: 30px auto;
            max-width: 800px;
        }
        .result-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .result-body {
            padding: 40px;
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 48px;
            font-weight: bold;
        }
        .status-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
        }
        .status-lulus {
            background: #d4edda;
            color: #155724;
        }
        .status-tidak-lulus {
            background: #f8d7da;
            color: #721c24;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <div class="result-header">
                <h2><i class="fas fa-award"></i> Hasil Ujian</h2>
                <p class="mb-0"><?= htmlspecialchars($hasil['ujian_nama']) ?></p>
            </div>
            
            <div class="result-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Data Peserta</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Nama</strong></td>
                                <td>:</td>
                                <td><?= htmlspecialchars($hasil['peserta_nama']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Kelas</strong></td>
                                <td>:</td>
                                <td><?= htmlspecialchars($hasil['kelas_nama']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Paket Soal</strong></td>
                                <td>:</td>
                                <td><?= htmlspecialchars($hasil['paket_nama']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Waktu Mulai</strong></td>
                                <td>:</td>
                                <td><?= date('d/m/Y H:i', strtotime($hasil['waktu_mulai'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Waktu Selesai</strong></td>
                                <td>:</td>
                                <td><?= date('d/m/Y H:i', strtotime($hasil['waktu_selesai'])) ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6 text-center">
                        <div class="score-circle">
                            <?= $hasil['nilai'] ?>
                        </div>
                        <div class="status-badge status-<?= $hasil['status'] ?>">
                            <?= $hasil['status'] == 'lulus' ? '<i class="fas fa-check-circle"></i> LULUS' : '<i class="fas fa-times-circle"></i> TIDAK LULUS' ?>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h5 class="text-center mb-4">Statistik Jawaban</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-success"><?= $hasil['benar'] ?></div>
                            <div class="stat-label">Jawaban Benar</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-danger"><?= $hasil['salah'] ?></div>
                            <div class="stat-label">Jawaban Salah</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-warning"><?= $hasil['kosong'] ?></div>
                            <div class="stat-label">Tidak Dijawab</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-info"><?= $total_soal ?></div>
                            <div class="stat-label">Total Soal</div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>