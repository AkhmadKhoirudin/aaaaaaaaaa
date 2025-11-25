<?php
session_start();
require_once '../config/database.php';

// Cek apakah ini request dari peserta yang sedang ujian
if (!isset($_SESSION['peserta_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['type']) || !isset($data['sesi_id'])) {
    http_response_code(400);
    exit('Invalid data');
}

$peserta_id = $_SESSION['peserta_id'];
$sesi_id = (int)$data['sesi_id'];
$violation_type = $data['type'];
$violation_count = (int)($data['violation_count'] ?? 1);

// Validasi bahwa sesi ini milik peserta
$stmt = $pdo->prepare("SELECT id FROM sesi_ujian WHERE id = ? AND peserta_id = ?");
$stmt->execute([$sesi_id, $peserta_id]);
$sesi = $stmt->fetch();

if (!$sesi) {
    http_response_code(403);
    exit('Invalid session');
}

// Simpan log violation
$stmt = $pdo->prepare("INSERT INTO log_violation (sesi_ujian_id, violation_type, violation_count, timestamp) 
                      VALUES (?, ?, ?, NOW())");
$stmt->execute([$sesi_id, $violation_type, $violation_count]);

// Update jumlah pelanggaran di sesi_ujian
$stmt = $pdo->prepare("UPDATE sesi_ujian SET jumlah_pelanggaran = ? WHERE id = ?");
$stmt->execute([$violation_count, $sesi_id]);

// Jika pelanggaran >= 3, force submit
if ($violation_count >= 3) {
    // Force submit akan dilakukan di client-side
    echo json_encode(['force_submit' => true]);
} else {
    echo json_encode(['status' => 'logged']);
}
?>