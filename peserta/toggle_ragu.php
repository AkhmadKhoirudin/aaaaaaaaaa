<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['peserta_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$sesi_id = $_POST['sesi_id'] ?? 0;
$soal_id = $_POST['soal_id'] ?? 0;

if ($sesi_id == 0 || $soal_id == 0) {
    http_response_code(400);
    exit('Invalid data');
}

// Validasi sesi
$stmt = $pdo->prepare("SELECT peserta_id FROM sesi_ujian WHERE id = ?");
$stmt->execute([$sesi_id]);
$sesi = $stmt->fetch();

if (!$sesi || $sesi['peserta_id'] != $_SESSION['peserta_id']) {
    http_response_code(403);
    exit('Unauthorized');
}

// Toggle ragu-ragu
$stmt = $pdo->prepare("SELECT ragu_ragu FROM jawaban_peserta WHERE sesi_ujian_id = ? AND soal_id = ?");
$stmt->execute([$sesi_id, $soal_id]);
$current = $stmt->fetchColumn();

if ($current === false) {
    // Insert new with ragu_ragu = 1
    $stmt = $pdo->prepare("INSERT INTO jawaban_peserta (sesi_ujian_id, soal_id, ragu_ragu) VALUES (?, ?, 1)");
    $stmt->execute([$sesi_id, $soal_id]);
} else {
    // Toggle existing
    $new_value = $current == 1 ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE jawaban_peserta SET ragu_ragu = ? WHERE sesi_ujian_id = ? AND soal_id = ?");
    $stmt->execute([$new_value, $sesi_id, $soal_id]);
}

echo json_encode(['status' => 'success']);
?>