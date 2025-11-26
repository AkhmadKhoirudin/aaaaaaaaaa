<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data untuk berita acara
$ujian_id = $_GET['ujian_id'] ?? 0;

$query = "SELECT u.*, p.nama_paket, m.nama_mapel, k.nama_kelas, s.nama_sekolah,
          COUNT(ps.id) as jumlah_peserta
          FROM ujian u
          JOIN paket_soal p ON u.paket_soal_id = p.id
          JOIN mata_pelajaran m ON p.mata_pelajaran_id = m.id
          LEFT JOIN kelas k ON p.kelas_id = k.id
          JOIN sekolah s ON k.sekolah_id = s.id
          LEFT JOIN peserta ps ON ps.kelas_id = k.id
          WHERE u.id = ?
          GROUP BY u.id";

$stmt = $pdo->prepare($query);
$stmt->execute([$ujian_id]);
$ujian_data = $stmt->fetch();

if (!$ujian_data) {
    die('Data ujian tidak ditemukan');
}

// Ambil peserta yang mengikuti ujian
$peserta_query = "SELECT p.*, u.nama_lengkap, us.mulai_ujian, us.selesai_ujian, us.nilai
                  FROM peserta p
                  JOIN users u ON p.user_id = u.id
                  LEFT JOIN sesi_ujian su ON su.peserta_id = p.id AND su.ujian_id = ?
                  WHERE p.kelas_id = ?
                  ORDER BY u.nama_lengkap";

$peserta_stmt = $pdo->prepare($peserta_query);
$peserta_stmt->execute([$ujian_id, $ujian_data['kelas_id']]);
$peserta = $peserta_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Berita Acara Ujian</title>
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
        <h2>BERITA ACARA PELAKSANAAN UJIAN</h2>
        <h3><?= htmlspecialchars($ujian_data['nama_sekolah']) ?></h3>
    </div>

    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 200px;">Nama Ujian</td>
            <td>: <?= htmlspecialchars($ujian_data['nama_ujian']) ?></td>
        </tr>
        <tr>
            <td>Mata Pelajaran</td>
            <td>: <?= htmlspecialchars($ujian_data['nama_mapel']) ?></td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: <?= htmlspecialchars($ujian_data['nama_kelas']) ?></td>
        </tr>
        <tr>
            <td>Tanggal Ujian</td>
            <td>: <?= date('d/m/Y', strtotime($ujian_data['tanggal_ujian'])) ?></td>
        </tr>
        <tr>
            <td>Waktu Ujian</td>
            <td>: <?= date('H:i', strtotime($ujian_data['tanggal_ujian'])) ?> - <?= date('H:i', strtotime($ujian_data['tanggal_ujian']) + $ujian_data['durasi'] * 60) ?></td>
        </tr>
        <tr>
            <td>Jumlah Peserta</td>
            <td>: <?= $ujian_data['jumlah_peserta'] ?> orang</td>
        </tr>
    </table>

    <h4>Daftar Peserta:</h4>
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Peserta</th>
                <th>Nilai</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($peserta as $index => $p): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($p['nis']) ?></td>
                <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                <td><?= $p['nilai'] ?? '-' ?></td>
                <td><?= $p['mulai_ujian'] ? 'Hadir' : 'Tidak Hadir' ?></td>
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