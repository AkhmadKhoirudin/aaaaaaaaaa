<?php
// Set header untuk download file CSV dengan encoding yang tepat
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="template_peserta.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Tambahkan BOM untuk Excel compatibility
echo "\xEF\xBB\xBF";

// Buka output stream
$output = fopen('php://output', 'w');

// Set locale untuk format yang konsisten
setlocale(LC_ALL, 'en_US.UTF-8');

// Tulis header CSV
fputcsv($output, array('No', 'Nama', 'Kelas', 'Jenis Kelamin', 'Username', 'Password', 'Status Aksi'));

// Tulis contoh data
fputcsv($output, array('1', 'Budi Santoso', 'XII RPL 1', 'L', 'budi123', 'password123', 'aktif'));
fputcsv($output, array('2', 'Siti Nurhaliza', 'XII RPL 2', 'P', 'siti123', 'password123', 'aktif'));
fputcsv($output, array('3', 'Andi Wijaya', 'XII TKJ 1', 'L', 'andi123', 'password123', 'aktif'));

// Tulis instruksi di baris terakhir sebagai komentar
fputcsv($output, array('', '', '', '', '', '', ''));
fputcsv($output, array('# INSTRUKSI:', '', '', '', '', '', ''));
fputcsv($output, array('# - Isi data sesuai kolom yang tersedia', '', '', '', '', '', ''));
fputcsv($output, array('# - Jenis Kelamin: L (Laki-laki) atau P (Perempuan)', '', '', '', '', '', ''));
fputcsv($output, array('# - Status Aksi: aktif atau nonaktif', '', '', '', '', '', ''));

// Tutup output stream
fclose($output);
exit;
?>