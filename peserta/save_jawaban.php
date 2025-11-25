<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['peserta_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$sesi_id = $_POST['sesi_id'] ?? 0;
$soal_id = $_POST['soal_id'] ?? 0;
$jawaban = $_POST['jawaban'] ?? '';

if ($sesi_id == 0 || $soal_id == 0) {
    http_response_code(400);
    exit('Invalid data');
}

// Validasi bahwa sesi ini milik peserta yang login
$stmt = $pdo->prepare("SELECT peserta_id FROM sesi_ujian WHERE id = ?");
$stmt->execute([$sesi_id]);
$sesi = $stmt->fetch();

if (!$sesi || $sesi['peserta_id'] != $_SESSION['peserta_id']) {
    http_response_code(403);
    exit('Unauthorized');
}

// Simpan atau update jawaban
$stmt = $pdo->prepare("INSERT INTO jawaban_peserta (sesi_ujian_id, soal_id, jawaban, waktu_jawab) 
                      VALUES (?, ?, ?, NOW()) 
                      ON DUPLICATE KEY UPDATE 
                      jawaban = VALUES(jawaban), 
                      waktu_jawab = VALUES(waktu_jawab)");
$stmt->execute([$sesi_id, $soal_id, $jawaban]);

echo json_encode(['status' => 'success']);
?>