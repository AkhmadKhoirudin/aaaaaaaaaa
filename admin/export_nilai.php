<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

require_once '../vendor/autoload.php';

// Ambil filter
$ujian_id = $_GET['ujian_id'] ?? 0;
$kelas_id = $_GET['kelas_id'] ?? 0;

// Query untuk data nilai
$where = [];
$params = [];

if ($ujian_id > 0) {
    $where[] = "u.id = ?";
    $params[] = $ujian_id;
}

if ($kelas_id > 0) {
    $where[] = "k.id = ?";
    $params[] = $kelas_id;
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT 
        ps.nama as peserta_nama,
        ps.nis,
        k.nama_kelas,
        u.nama as ujian_nama,
        su.nilai,
        su.benar,
        su.salah,
        su.kosong,
        su.waktu_mulai,
        su.waktu_selesai,
        TIMESTAMPDIFF(MINUTE, su.waktu_mulai, su.waktu_selesai) as durasi_menit
    FROM sesi_ujian su
    JOIN peserta ps ON su.peserta_id = ps.id
    JOIN kelas k ON ps.kelas_id = k.id
    JOIN ujian u ON su.ujian_id = u.id
    $where_clause
    AND su.status = 'selesai'
    ORDER BY k.nama_kelas, ps.nama
");
$stmt->execute($params);
$nilai_data = $stmt->fetchAll();

// Create spreadsheet
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'DAFTAR NILAI UJIAN');
$sheet->mergeCells('A1:J1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Set column headers
$sheet->setCellValue('A3', 'No');
$sheet->setCellValue('B3', 'Nama Peserta');
$sheet->setCellValue('C3', 'NIS');
$sheet->setCellValue('D3', 'Kelas');
$sheet->setCellValue('E3', 'Ujian');
$sheet->setCellValue('F3', 'Nilai');
$sheet->setCellValue('G3', 'Benar');
$sheet->setCellValue('H3', 'Salah');
$sheet->setCellValue('I3', 'Kosong');
$sheet->setCellValue('J3', 'Durasi (Menit)');

// Style headers
$headerStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
];
$sheet->getStyle('A3:J3')->applyFromArray($headerStyle);

// Fill data
$row = 4;
foreach ($nilai_data as $index => $data) {
    $sheet->setCellValue('A' . $row, $index + 1);
    $sheet->setCellValue('B' . $row, $data['peserta_nama']);
    $sheet->setCellValue('C' . $row, $data['nis']);
    $sheet->setCellValue('D' . $row, $data['nama_kelas']);
    $sheet->setCellValue('E' . $row, $data['ujian_nama']);
    $sheet->setCellValue('F' . $row, $data['nilai']);
    $sheet->setCellValue('G' . $row, $data['benar']);
    $sheet->setCellValue('H' . $row, $data['salah']);
    $sheet->setCellValue('I' . $row, $data['kosong']);
    $sheet->setCellValue('J' . $row, $data['durasi_menit']);
    
    // Color coding for nilai
    if ($data['nilai'] >= 80) {
        $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('28a745');
    } elseif ($data['nilai'] >= 70) {
        $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('ffc107');
    } else {
        $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('dc3545');
    }
    
    $row++;
}

// Auto size columns
foreach (range('A', 'J') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Add borders to all data
$sheet->getStyle('A3:J' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// Add summary
$sheet->setCellValue('A' . ($row + 2), 'Ringkasan:');
$sheet->getStyle('A' . ($row + 2))->getFont()->setBold(true);

$sheet->setCellValue('A' . ($row + 3), 'Total Peserta:');
$sheet->setCellValue('B' . ($row + 3), $total_peserta);

$sheet->setCellValue('A' . ($row + 4), 'Rata-rata Nilai:');
$sheet->setCellValue('B' . ($row + 4), $total_peserta > 0 ? round(array_sum(array_column($nilai_data, 'nilai')) / $total_peserta, 2) : 0);

$sheet->setCellValue('A' . ($row + 5), 'Nilai Tertinggi:');
$sheet->setCellValue('B' . ($row + 5), $total_peserta > 0 ? max(array_column($nilai_data, 'nilai')) : 0);

$sheet->setCellValue('A' . ($row + 6), 'Nilai Terendah:');
$sheet->setCellValue('B' . ($row + 6), $total_peserta > 0 ? min(array_column($nilai_data, 'nilai')) : 0);

// Set filename
$filename = 'nilai_ujian_' . date('Y-m-d_H-i-s') . '.xlsx';

// Download file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit();
?>