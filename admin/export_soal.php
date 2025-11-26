<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Proses export
if (isset($_GET['paket_soal_id'])) {
    require_once '../vendor/autoload.php';
    
    $paket_soal_id = $_GET['paket_soal_id'];
    
    // Ambil data soal
    $stmt = $pdo->prepare("SELECT * FROM bank_soal WHERE mata_pelajaran_id = ? ORDER BY id");
    $stmt->execute([$paket_soal_id]);
    $soal = $stmt->fetchAll();
    
    if (empty($soal)) {
        $_SESSION['error'] = "Tidak ada soal untuk paket ini";
        header("Location: bank_soal.php");
        exit();
    }
    
    // Create spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $sheet->setCellValue('A1', 'Jenis Soal');
    $sheet->setCellValue('B1', 'Pertanyaan');
    $sheet->setCellValue('C1', 'Pilihan A');
    $sheet->setCellValue('D1', 'Pilihan B');
    $sheet->setCellValue('E1', 'Pilihan C');
    $sheet->setCellValue('F1', 'Pilihan D');
    $sheet->setCellValue('G1', 'Pilihan E');
    $sheet->setCellValue('H1', 'Jawaban Benar');
    $sheet->setCellValue('I1', 'Bobot Soal');
    $sheet->setCellValue('J1', 'Tingkat Kesulitan');
    
    // Style header
    $headerStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
    
    // Fill data
    $row = 2;
    foreach ($soal as $item) {
        $sheet->setCellValue('A' . $row, $item['jenis_soal']);
        $sheet->setCellValue('B' . $row, $item['pertanyaan']);
        $sheet->setCellValue('C' . $row, $item['pilihan_a']);
        $sheet->setCellValue('D' . $row, $item['pilihan_b']);
        $sheet->setCellValue('E' . $row, $item['pilihan_c']);
        $sheet->setCellValue('F' . $row, $item['pilihan_d']);
        $sheet->setCellValue('G' . $row, $item['pilihan_e']);
        $sheet->setCellValue('H' . $row, $item['jawaban_benar']);
        $sheet->setCellValue('I' . $row, $item['bobot_soal']);
        $sheet->setCellValue('J' . $row, $item['tingkat_kesulitan']);
        $row++;
    }
    
    // Auto size columns
    foreach (range('A', 'J') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Set filename
    $filename = 'soal_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Download file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit();
}

// Ambil daftar paket soal
$stmt = $pdo->query("SELECT id, nama_paket FROM paket_soal ORDER BY nama_paket");
$paket_soal = $stmt->fetchAll();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Export Soal ke Excel</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="bank_soal.php">Bank Soal</a></li>
                        <li class="breadcrumb-item active">Export Soal</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pilih Paket Soal untuk Export</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form method="get">
                                <div class="form-group">
                                    <label>Paket Soal</label>
                                    <select name="paket_soal_id" class="form-control" required>
                                        <option value="">-- Pilih Paket Soal --</option>
                                        <?php foreach ($paket_soal as $paket): ?>
                                            <option value="<?= $paket['id'] ?>"><?= htmlspecialchars($paket['nama_paket']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-download"></i> Export ke Excel
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Informasi</h5>
                                <p>File Excel akan berisi semua soal dari paket yang dipilih dengan format:</p>
                                <ul>
                                    <li>Jenis Soal</li>
                                    <li>Pertanyaan</li>
                                    <li>Pilihan A-E</li>
                                    <li>Jawaban Benar</li>
                                    <li>Bobot Soal</li>
                                    <li>Tingkat Kesulitan</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer-modern.php'; ?>