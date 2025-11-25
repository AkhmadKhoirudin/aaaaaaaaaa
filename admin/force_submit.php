<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$sesi_id = $_POST['sesi_id'] ?? 0;

if ($sesi_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit();
}

try {
    // Ambil data sesi
    $stmt = $pdo->prepare("SELECT * FROM sesi_ujian WHERE id = ? AND status = 'sedang_ujian'");
    $stmt->execute([$sesi_id]);
    $sesi = $stmt->fetch();

    if (!$sesi) {
        echo json_encode(['success' => false, 'message' => 'Sesi tidak ditemukan atau sudah selesai']);
        exit();
    }

    // Hitung nilai
    $stmt = $pdo->prepare("SELECT s.jawaban_benar, jp.jawaban 
                          FROM bank_soal s 
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

    $total_soal = count($jawabans);
    $nilai = $total_soal > 0 ? round(($benar / $total_soal) * 100) : 0;

    // Update sesi dengan force submit
    $stmt = $pdo->prepare("UPDATE sesi_ujian SET 
                          waktu_selesai = NOW(), 
                          status = 'force_submit', 
                          nilai = ?, 
                          benar = ?, 
                          salah = ?, 
                          kosong = ? 
                          WHERE id = ?");
    $stmt->execute([$nilai, $benar, $salah, $kosong, $sesi_id]);

    // Update peserta_ujian
    $stmt = $pdo->prepare("UPDATE peserta_ujian SET status = 'selesai' 
                          WHERE peserta_id = ? AND ujian_id = ?");
    $stmt->execute([$sesi['peserta_id'], $sesi['ujian_id']]);

    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity, details) 
                          VALUES (?, 'force_submit_ujian', ?)");
    $stmt->execute([$_SESSION['user_id'], "Force submit ujian ID: {$sesi['ujian_id']} untuk peserta ID: {$sesi['peserta_id']}"]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>