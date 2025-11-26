<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();
if (!hasRole(['admin', 'guru'])) {
    header('Location: dashboard.php');
    exit;
}

// Set page title
$page_title = 'Import Soal';

include 'includes/header-modern.php';

// Proses import jika ada file yang diupload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_excel'])) {
    require_once '../vendor/autoload.php';
    
    $file = $_FILES['file_excel'];
    $paket_soal_id = $_POST['paket_soal_id'] ?? 0;
    
    if ($file['error'] == 0 && $paket_soal_id > 0) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $success_count = 0;
            $error_count = 0;
            
            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Validasi data
                if (empty($row[0]) || empty($row[1])) continue;
                
                $jenis_soal = strtolower($row[0]);
                $pertanyaan = $row[1];
                $pilihan_a = $row[2] ?? '';
                $pilihan_b = $row[3] ?? '';
                $pilihan_c = $row[4] ?? '';
                $pilihan_d = $row[5] ?? '';
                $pilihan_e = $row[6] ?? '';
                $jawaban_benar = strtoupper($row[7] ?? '');
                $bobot_soal = (int)($row[8] ?? 1);
                $tingkat_kesulitan = strtolower($row[9] ?? 'sedang');
                
                // Validasi jenis soal
                if (!in_array($jenis_soal, ['pilihan_ganda', 'esai'])) {
                    $error_count++;
                    continue;
                }
                
                // Validasi tingkat kesulitan
                if (!in_array($tingkat_kesulitan, ['mudah', 'sedang', 'sulit'])) {
                    $tingkat_kesulitan = 'sedang';
                }
                
                // Validasi jawaban untuk pilihan ganda
                if ($jenis_soal == 'pilihan_ganda' && !in_array($jawaban_benar, ['A', 'B', 'C', 'D', 'E'])) {
                    $error_count++;
                    continue;
                }
                
                try {
                    // Ambil data mata_pelajaran_id dan kelas_id dari paket_soal
                    $paket_query = $pdo->prepare("SELECT mata_pelajaran_id, kelas_id FROM paket_soal WHERE id = ?");
                    $paket_query->execute([$paket_soal_id]);
                    $paket_data = $paket_query->fetch();
                    
                    if (!$paket_data) {
                        $error_count++;
                        continue;
                    }
                    
                    // Ambil guru_id dari user yang sedang login (jika rolenya guru)
                    $guru_id = null;
                    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'guru') {
                        $guru_id = $_SESSION['user_id'];
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO bank_soal
                        (mata_pelajaran_id, kelas_id, guru_id, jenis_soal, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e,
                         jawaban_benar, bobot_soal, tingkat_kesulitan)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $paket_data['mata_pelajaran_id'],
                        $paket_data['kelas_id'],
                        $guru_id,
                        $jenis_soal,
                        $pertanyaan,
                        $pilihan_a,
                        $pilihan_b,
                        $pilihan_c,
                        $pilihan_d,
                        $pilihan_e,
                        $jawaban_benar,
                        $bobot_soal,
                        $tingkat_kesulitan
                    ]);
                    
                    $success_count++;
                } catch (PDOException $e) {
                    $error_count++;
                }
            }
            
            $_SESSION['import_result'] = [
                'success' => $success_count,
                'error' => $error_count
            ];
            
        } catch (Exception $e) {
            $_SESSION['import_error'] = "Error: " . $e->getMessage();
        }
    }
    
    header("Location: import_soal.php");
    exit();
}

// Ambil daftar paket soal
$stmt = $pdo->query("SELECT ps.id, ps.nama_paket, mp.nama_mapel, k.nama_kelas
                     FROM paket_soal ps
                     JOIN mata_pelajaran mp ON ps.mata_pelajaran_id = mp.id
                     JOIN kelas k ON ps.kelas_id = k.id
                     ORDER BY ps.nama_paket");
$paket_soal = $stmt->fetchAll();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Import Soal dari Excel</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="bank_soal.php">Bank Soal</a></li>
                        <li class="breadcrumb-item active">Import Soal</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['import_result'])): ?>
                <div class="alert alert-info">
                    <strong>Hasil Import:</strong><br>
                    Berhasil: <?= $_SESSION['import_result']['success'] ?> soal<br>
                    Gagal: <?= $_SESSION['import_result']['error'] ?> soal
                </div>
                <?php unset($_SESSION['import_result']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['import_error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['import_error'] ?>
                </div>
                <?php unset($_SESSION['import_error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload File Excel</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Paket Soal</label>
                                    <select name="paket_soal_id" class="form-control" required>
                                        <option value="">-- Pilih Paket Soal --</option>
                                        <?php foreach ($paket_soal as $paket): ?>
                                            <option value="<?= $paket['id'] ?>"><?= htmlspecialchars($paket['nama_paket']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>File Excel</label>
                                    <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls" required>
                                    <small class="text-muted">
                                        Format: .xlsx atau .xls<br>
                                        Maksimal ukuran: 2MB
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Import Soal
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Format Excel</h5>
                                </div>
                                <div class="card-body">
                                    <p>File Excel harus memiliki format sebagai berikut:</p>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Kolom</th>
                                                <th>Isi</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>A</td>
                                                <td>Jenis Soal</td>
                                                <td>pilihan_ganda atau esai</td>
                                            </tr>
                                            <tr>
                                                <td>B</td>
                                                <td>Pertanyaan</td>
                                                <td>Teks pertanyaan</td>
                                            </tr>
                                            <tr>
                                                <td>C</td>
                                                <td>Pilihan A</td>
                                                <td>Jawaban pilihan A (untuk pilihan ganda)</td>
                                            </tr>
                                            <tr>
                                                <td>D</td>
                                                <td>Pilihan B</td>
                                                <td>Jawaban pilihan B (untuk pilihan ganda)</td>
                                            </tr>
                                            <tr>
                                                <td>E</td>
                                                <td>Pilihan C</td>
                                                <td>Jawaban pilihan C (untuk pilihan ganda)</td>
                                            </tr>
                                            <tr>
                                                <td>F</td>
                                                <td>Pilihan D</td>
                                                <td>Jawaban pilihan D (untuk pilihan ganda)</td>
                                            </tr>
                                            <tr>
                                                <td>G</td>
                                                <td>Pilihan E</td>
                                                <td>Jawaban pilihan E (untuk pilihan ganda)</td>
                                            </tr>
                                            <tr>
                                                <td>H</td>
                                                <td>Jawaban Benar</td>
                                                <td>A/B/C/D/E (untuk pilihan ganda)</td>
                                            </tr>
                                            <tr>
                                                <td>I</td>
                                                <td>Bobot Soal</td>
                                                <td>Angka (default: 1)</td>
                                            </tr>
                                            <tr>
                                                <td>J</td>
                                                <td>Tingkat Kesulitan</td>
                                                <td>mudah/sedang/sulit</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <a href="template_soal.xlsx" class="btn btn-success">
                                        <i class="fas fa-download"></i> Download Template
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer-modern.php'; ?>