<?php
session_start();
require_once '../config/database.php';

// Cek apakah ini request dari peserta yang sedang ujian
if (!isset($_SESSION['peserta_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['image']) || !isset($data['sesi_id'])) {
    http_response_code(400);
    exit('Invalid data');
}

$peserta_id = $_SESSION['peserta_id'];
$sesi_id = (int)$data['sesi_id'];
$image_data = $data['image'];
$timestamp = $data['timestamp'] ?? time();

// Validasi bahwa sesi ini milik peserta
$stmt = $pdo->prepare("SELECT id FROM sesi_ujian WHERE id = ? AND peserta_id = ?");
$stmt->execute([$sesi_id, $peserta_id]);
$sesi = $stmt->fetch();

if (!$sesi) {
    http_response_code(403);
    exit('Invalid session');
}

// Decode base64 image
$image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
$image_data = str_replace(' ', '+', $image_data);
$image_binary = base64_decode($image_data);

if (!$image_binary) {
    http_response_code(400);
    exit('Invalid image data');
}

// Create directory if not exists
$upload_dir = '../uploads/screenshots/' . date('Y-m-d') . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate filename
$filename = 'screenshot_' . $sesi_id . '_' . $timestamp . '.jpg';
$file_path = $upload_dir . $filename;

// Save image
if (file_put_contents($file_path, $image_binary)) {
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO screenshots (sesi_ujian_id, filename, filepath, timestamp) 
                          VALUES (?, ?, ?, FROM_UNIXTIME(?))");
    $stmt->execute([$sesi_id, $filename, $file_path, $timestamp]);
    
    echo json_encode(['status' => 'success', 'filename' => $filename]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save screenshot']);
}
?>