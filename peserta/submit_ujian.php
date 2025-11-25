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

// Validasi sesi
$stmt = $pdo->prepare("SELECT su.*, u.paket_soal_id, p.jumlah_soal, p.passing_grade 
                      FROM sesi_ujian su 
                      JOIN ujian u ON su.ujian_id = u.id 
                      JOIN paket_soal p ON u.paket_soal_id = p.id 
                      WHERE su.id = ? AND su.peserta_id = ?");
$stmt->execute([$sesi_id, $peserta_id]);
$sesi = $stmt->fetch();

if (!$sesi) {
    header("Location: dashboard.php");
    exit();
}

// Hitung nilai
$stmt = $pdo->prepare("SELECT s.id, s.jawaban_benar, jp.jawaban 
                      FROM soal s 
                      JOIN jawaban_peserta jp ON s.id = jp.soal_id 
                      WHERE jp.sesi_ujian_id = ?");
$stmt->execute([$sesi_id]);
$jawabans = $stmt->fetchAll();

$benar = 0;
$salah = 0;
$kosong = 0;

foreach ($jawabans as $j) {
    if (empty($j['jawaban'])) {
        $kosong++;
    } elseif (strtoupper($j['jawaban']) == strtoupper($j['jawaban_benar'])) {
        $benar++;
    } else {
        $salah++;
    }
}

$total_soal = $sesi['jumlah_soal'];
$nilai = ($total_soal > 0) ? round(($benar / $total_soal) * 100) : 0;
$status = ($nilai >= $sesi['passing_grade']) ? 'lulus' : 'tidak_lulus';

// Update sesi ujian
$stmt = $pdo->prepare("UPDATE sesi_ujian SET 
                      waktu_selesai = NOW(), 
                      status = 'selesai', 
                      nilai = ?, 
                      benar = ?, 
                      salah = ?, 
                      kosong = ? 
                      WHERE id = ?");
$stmt->execute([$nilai, $benar, $salah, $kosong, $sesi_id]);

// Update status peserta ujian
$stmt = $pdo->prepare("UPDATE peserta_ujian SET status = 'selesai' 
                      WHERE peserta_id = ? AND ujian_id = ?");
$stmt->execute([$peserta_id, $sesi['ujian_id']]);

// Redirect ke halaman hasil
header("Location: hasil_ujian.php?sesi_id=" . $sesi_id);
exit();
?>