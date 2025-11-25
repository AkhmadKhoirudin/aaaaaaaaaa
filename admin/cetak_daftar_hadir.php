<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data untuk daftar hadir
$ujian_id = $_GET['ujian_id'] ?? 0;
$kelas_id = $_GET['kelas_id'] ?? 0;

$query = "SELECT p.*, k.nama_kelas, u.nama_lengkap, s.nama_sekolah
          FROM peserta p
          JOIN users u ON p.user_id = u.id
          JOIN kelas k ON p.kelas_id = k.id
          JOIN sekolah s ON k.sekolah_id = s.id
          WHERE p.kelas_id = ?
          ORDER BY u.nama_lengkap";

$stmt = $pdo->prepare($query);
$stmt->execute([$kelas_id]);
$peserta = $stmt->fetchAll();

// Ambil data ujian
$ujian = $pdo->prepare("SELECT * FROM ujian WHERE id = ?");
$ujian->execute([$ujian_id]);
$ujian_data = $ujian->fetch();

if (!$ujian_data) {
    die('Data ujian tidak ditemukan');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Hadir Ujian</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .signature { margin-top: 50px; }
        .signature-table { width: 100%; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>DAFTAR HADIR PESERTA UJIAN</h2>
        <h3><?= htmlspecialchars($ujian_data['nama_ujian']) ?></h3>
        <p>Mata Pelajaran: <?= htmlspecialchars($ujian_data['nama_mapel']) ?></p>
        <p>Kelas: <?= htmlspecialchars($peserta[0]['nama_kelas'] ?? '') ?></p>
        <p>Tanggal: <?= date('d/m/Y', strtotime($ujian_data['tanggal_ujian'])) ?></p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Peserta</th>
                <th>Tanda Tangan</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($peserta as $index => $p): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($p['nis']) ?></td>
                <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                <td style="width: 150px;"></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signature">
        <table class="signature-table">
            <tr>
                <td style="text-align: center;">
                    <p>Mengetahui,</p>
                    <p>Kepala Sekolah</p>
                    <br><br><br>
                    <p>____________________</p>
                </td>
                <td style="text-align: center;">
                    <p><?= date('d/m/Y') ?></p>
                    <p>Pengawas Ujian</p>
                    <br><br><br>
                    <p>____________________</p>
                </td>
            </tr>
        </table>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>