<?php
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set judul kolom
$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Nama');
$sheet->setCellValue('C1', 'Kelas');
$sheet->setCellValue('D1', 'Jenis Kelamin');
$sheet->setCellValue('E1', 'Username');
$sheet->setCellValue('F1', 'Password');
$sheet->setCellValue('G1', 'Status Aksi');

// Set lebar kolom
$sheet->getColumnDimension('A')->setWidth(10);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(15);

// Set style header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ],
];

$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Tambahkan contoh data
$sheet->setCellValue('A2', '1');
$sheet->setCellValue('B2', 'Budi Santoso');
$sheet->setCellValue('C2', 'XII RPL 1');
$sheet->setCellValue('D2', 'L');
$sheet->setCellValue('E2', 'budi123');
$sheet->setCellValue('F2', 'password123');
$sheet->setCellValue('G2', 'Aktif');

$sheet->setCellValue('A3', '2');
$sheet->setCellValue('B3', 'Siti Nurhaliza');
$sheet->setCellValue('C3', 'XII RPL 2');
$sheet->setCellValue('D3', 'P');
$sheet->setCellValue('E3', 'siti123');
$sheet->setCellValue('F3', 'password123');
$sheet->setCellValue('G3', 'Aktif');

// Tambahkan keterangan
$sheet->setCellValue('A5', 'Keterangan:');
$sheet->setCellValue('A6', '- Kolom Jenis Kelamin: L = Laki-laki, P = Perempuan');
$sheet->setCellValue('A7', '- Kolom Status Aksi: Aktif/Nonaktif');
$sheet->setCellValue('A8', '- Kolom Kelas: Isi dengan nama kelas yang sudah terdaftar di sistem');
$sheet->setCellValue('A9', '- Username: Harus unik, tidak boleh sama dengan yang sudah ada');

// Set border
$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

$sheet->getStyle('A1:G3')->applyFromArray($borderStyle);

// Set nama file dan download
$filename = 'template_peserta.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>