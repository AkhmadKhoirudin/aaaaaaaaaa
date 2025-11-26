<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();
if (!hasRole(['admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Set page title
$page_title = 'Cetak Kartu Peserta';

include 'includes/header-modern.php';

// Ambil data untuk filter
$stmt = $pdo->query("SELECT id, nama FROM ujian ORDER BY nama");
$ujians = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
$kelas = $stmt->fetchAll();

// Proses cetak
if (isset($_POST['cetak'])) {
    $ujian_id = $_POST['ujian_id'] ?? 0;
    $kelas_id = $_POST['kelas_id'] ?? 0;
    
    // Ambil data peserta
    $where = [];
    $params = [];
    
    if ($ujian_id > 0) {
        $where[] = "pu.ujian_id = ?";
        $params[] = $ujian_id;
    }
    
    if ($kelas_id > 0) {
        $where[] = "ps.kelas_id = ?";
        $params[] = $kelas_id;
    }
    
    $where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            u.nama_lengkap as nama,
            ps.nis,
            ps.nisn,
            k.nama_kelas,
            u.username,
            ps.token_ujian,
            GROUP_CONCAT(DISTINCT uj.nama SEPARATOR ', ') as ujian_nama,
            GROUP_CONCAT(DISTINCT ru.kode_ruang SEPARATOR ', ') as kode_ruang,
            GROUP_CONCAT(DISTINCT ru.nama_ruang SEPARATOR ', ') as nama_ruang
        FROM peserta ps
        JOIN users u ON ps.user_id = u.id
        JOIN kelas k ON ps.kelas_id = k.id
        LEFT JOIN peserta_ujian pu ON ps.id = pu.peserta_id
        LEFT JOIN ujian uj ON pu.ujian_id = uj.id
        LEFT JOIN jadwal_ujian ju ON uj.id = ju.ujian_id
        LEFT JOIN ruang_ujian ru ON ju.ruang_ujian_id = ru.id
        $where_clause
        GROUP BY ps.id, u.nama_lengkap, ps.nis, ps.nisn, k.nama_kelas, u.username, ps.token_ujian
        ORDER BY k.nama_kelas, u.nama_lengkap
    ");
    $stmt->execute($params);
    $peserta = $stmt->fetchAll();
    
    if (empty($peserta)) {
        $_SESSION['error'] = "Tidak ada data peserta untuk kriteria yang dipilih";
    } else {
        // Redirect ke halaman cetak
        $_SESSION['cetak_data'] = $peserta;
        header("Location: cetak_kartu_pdf.php");
        exit();
    }
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Cetak Kartu Peserta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Cetak Kartu Peserta</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filter Data Peserta</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ujian</label>
                                    <select name="ujian_id" class="form-control">
                                        <option value="0">-- Semua Ujian --</option>
                                        <?php foreach ($ujians as $ujian): ?>
                                            <option value="<?= $ujian['id'] ?>"><?= htmlspecialchars($ujian['nama']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kelas</label>
                                    <select name="kelas_id" class="form-control">
                                        <option value="0">-- Semua Kelas --</option>
                                        <?php foreach ($kelas as $k): ?>
                                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" name="cetak" class="btn btn-primary">
                                            <i class="fas fa-print"></i> Cetak Kartu
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Kartu -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Preview Kartu Peserta</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card" style="border: 2px solid #000; width: 350px; height: 200px;">
                                <div class="card-header text-center" style="background: #f8f9fa; padding: 10px;">
                                    <h6 style="margin: 0; font-size: 14px;"><strong>KARTU PESERTA UJIAN</strong></h6>
                                </div>
                                <div class="card-body" style="padding: 10px; font-size: 12px;">
                                    <div class="row">
                                        <div class="col-8">
                                            <p style="margin: 2px 0;"><strong>Nama:</strong> Nama Peserta</p>
                                            <p style="margin: 2px 0;"><strong>NIS:</strong> 1234567890</p>
                                            <p style="margin: 2px 0;"><strong>Kelas:</strong> XII RPL 1</p>
                                            <p style="margin: 2px 0;"><strong>Ujian:</strong> Matematika</p>
                                            <p style="margin: 2px 0;"><strong>Ruang:</strong> A101</p>
                                            <p style="margin: 2px 0;"><strong>Username:</strong> 1234567890</p>
                                            <p style="margin: 2px 0;"><strong>Password:</strong> 1234567890</p>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div style="border: 1px solid #ccc; width: 80px; height: 100px; margin: 0 auto;">
                                                <small style="line-height: 100px;">Foto</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small><strong>Token:</strong> ABC123XYZ</small>
                                    </div>
                                    <div class="text-center mt-1" style="font-size: 10px; background: #d4edda; padding: 2px; border: 1px solid #c3e6cb;">
                                        <strong>Login:</strong> Username: 1234567890 | Password: 1234567890
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Informasi Kartu</h5>
                            <ul>
                                <li>Ukuran: 350 x 200 px</li>
                                <li>Berisi data peserta lengkap</li>
                                <li>Dilengkapi foto, token ujian, dan login info</li>
                                <li>Format siap cetak</li>
                                <li>Bisa dicetak dalam jumlah banyak</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer-modern.php'; ?>