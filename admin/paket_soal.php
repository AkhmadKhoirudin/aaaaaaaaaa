<?php
// Pastikan session dimulai sebelum semua operasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator', 'guru'])) {
    header('Location: dashboard.php');
    exit;
}

// Proses form
if ($_POST) {
    if (isset($_POST['tambah_paket'])) {
        $mata_pelajaran_id = $_POST['mata_pelajaran_id'];
        $kelas_id = $_POST['kelas_id'];
        $jumlah_soal = $_POST['jumlah_soal'];
        $durasi_menit = $_POST['durasi_menit'];
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        $acak_soal = isset($_POST['acak_soal']) ? 1 : 0;
        $acak_jawaban = isset($_POST['acak_jawaban']) ? 1 : 0;
        $tampilkan_nilai = isset($_POST['tampilkan_nilai']) ? 1 : 0;
        
        // Ambil nama mata pelajaran dan kelas untuk generate kode dan nama otomatis
        $mapel = $pdo->prepare("SELECT nama_mapel FROM mata_pelajaran WHERE id = ?");
        $mapel->execute([$mata_pelajaran_id]);
        $nama_mapel = $mapel->fetchColumn();
        
        $kelas = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
        $kelas->execute([$kelas_id]);
        $nama_kelas = $kelas->fetchColumn();
        
        // Generate kode dan nama paket otomatis
        $kode_paket = generateKodePaket($nama_mapel, $nama_kelas);
        $nama_paket = generateNamaPaket($nama_mapel, $nama_kelas, $tanggal_mulai);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO paket_soal
                                 (kode_paket, nama_paket, mata_pelajaran_id, kelas_id, jumlah_soal,
                                  durasi_menit, tanggal_mulai, tanggal_selesai, acak_soal, acak_jawaban, tampilkan_nilai)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode_paket, $nama_paket, $mata_pelajaran_id, $kelas_id, $jumlah_soal,
                           $durasi_menit, $tanggal_mulai, $tanggal_selesai, $acak_soal, $acak_jawaban, $tampilkan_nilai]);
            
            $_SESSION['success'] = 'Paket soal berhasil ditambahkan dengan kode: ' . $kode_paket;
        } catch (PDOException $e) {
            // Jika kode sudah ada, coba dengan kode alternatif
            $kode_paket = generateKodePaketAlternatif($nama_mapel, $nama_kelas);
            try {
                $stmt = $pdo->prepare("INSERT INTO paket_soal
                                     (kode_paket, nama_paket, mata_pelajaran_id, kelas_id, jumlah_soal,
                                      durasi_menit, tanggal_mulai, tanggal_selesai, acak_soal, acak_jawaban, tampilkan_nilai)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$kode_paket, $nama_paket, $mata_pelajaran_id, $kelas_id, $jumlah_soal,
                               $durasi_menit, $tanggal_mulai, $tanggal_selesai, $acak_soal, $acak_jawaban, $tampilkan_nilai]);
                
                $_SESSION['success'] = 'Paket soal berhasil ditambahkan dengan kode: ' . $kode_paket;
            } catch (PDOException $e2) {
                $_SESSION['error'] = 'Gagal menambahkan paket soal. Silakan coba lagi.';
            }
        }
        
        header('Location: paket_soal.php');
        exit;
    }
    
    if (isset($_POST['edit_paket'])) {
        $id = $_POST['id'];
        $kode_paket = $_POST['kode_paket'];
        $nama_paket = $_POST['nama_paket'];
        $mata_pelajaran_id = $_POST['mata_pelajaran_id'];
        $kelas_id = $_POST['kelas_id'];
        $jumlah_soal = $_POST['jumlah_soal'];
        $durasi_menit = $_POST['durasi_menit'];
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        $acak_soal = isset($_POST['acak_soal']) ? 1 : 0;
        $acak_jawaban = isset($_POST['acak_jawaban']) ? 1 : 0;
        $tampilkan_nilai = isset($_POST['tampilkan_nilai']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE paket_soal SET 
                                 kode_paket = ?, nama_paket = ?, mata_pelajaran_id = ?, kelas_id = ?, 
                                 jumlah_soal = ?, durasi_menit = ?, tanggal_mulai = ?, tanggal_selesai = ?, 
                                 acak_soal = ?, acak_jawaban = ?, tampilkan_nilai = ? 
                                 WHERE id = ?");
            $stmt->execute([$kode_paket, $nama_paket, $mata_pelajaran_id, $kelas_id,
                           $jumlah_soal, $durasi_menit, $tanggal_mulai, $tanggal_selesai,
                           $acak_soal, $acak_jawaban, $tampilkan_nilai, $id]);
            
            $_SESSION['success'] = 'Paket soal berhasil diperbarui';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Kode paket sudah ada';
        }
        
        header('Location: paket_soal.php');
        exit;
    }
    
    if (isset($_POST['hapus_paket'])) {
        $id = $_POST['id'];
        
        // Cek apakah ada jadwal ujian yang menggunakan paket ini
        $check = $pdo->prepare("SELECT COUNT(*) FROM jadwal_ujian WHERE paket_soal_id = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus paket yang masih memiliki jadwal ujian';
        } else {
            $stmt = $pdo->prepare("DELETE FROM paket_soal WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Paket soal berhasil dihapus';
        }
        
        header('Location: paket_soal.php');
        exit;
    }
}

// Fungsi untuk generate kode paket otomatis
function generateKodePaket($nama_mapel, $nama_kelas) {
    global $pdo;
    
    // Ambil 3 huruf pertama dari nama mapel dan 2 huruf dari nama kelas
    $mapel_kode = strtoupper(substr(str_replace(' ', '', $nama_mapel), 0, 3));
    $kelas_kode = strtoupper(substr(str_replace(' ', '', $nama_kelas), 0, 2));
    
    // Gabungkan dan tambahkan angka
    $kode = $mapel_kode . $kelas_kode . date('m');
    
    // Cek apakah kode sudah ada di database
    $check = $pdo->prepare("SELECT COUNT(*) FROM paket_soal WHERE kode_paket = ?");
    $check->execute([$kode]);
    $count = $check->fetchColumn();
    
    // Jika kode sudah ada, gunakan fungsi alternatif
    if ($count > 0) {
        return generateKodePaketAlternatif($nama_mapel, $nama_kelas);
    }
    
    return $kode;
}

// Fungsi untuk generate kode paket alternatif jika kode pertama sudah ada
function generateKodePaketAlternatif($nama_mapel, $nama_kelas) {
    global $pdo;
    
    // Ambil 3 huruf pertama dari nama mapel dan 2 huruf dari nama kelas
    $mapel_kode = strtoupper(substr(str_replace(' ', '', $nama_mapel), 0, 3));
    $kelas_kode = strtoupper(substr(str_replace(' ', '', $nama_kelas), 0, 2));
    
    // Coba tambahkan angka 1-99
    for ($i = 1; $i <= 99; $i++) {
        $kode = $mapel_kode . $kelas_kode . str_pad($i, 2, '0', STR_PAD_LEFT);
        
        // Cek apakah kode sudah ada
        $check = $pdo->prepare("SELECT COUNT(*) FROM paket_soal WHERE kode_paket = ?");
        $check->execute([$kode]);
        $count = $check->fetchColumn();
        
        if ($count == 0) {
            return $kode;
        }
    }
    
    // Jika masih tidak ada, tambahkan timestamp
    return $mapel_kode . $kelas_kode . date('is');
}

// Fungsi untuk generate nama paket otomatis
function generateNamaPaket($nama_mapel, $nama_kelas, $tanggal_mulai) {
    // Format: Ujian [Mapel] Kelas [Kelas] - [Bulan Tahun]
    $tanggal = new DateTime($tanggal_mulai);
    $bulan_tahun = $tanggal->format('F Y');
    
    return "Ujian " . $nama_mapel . " Kelas " . $nama_kelas . " - " . $bulan_tahun;
}

// Ambil data untuk filter
$mata_pelajaran = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY nama_mapel")->fetchAll();
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

// Filter paket soal
$where = [];
$params = [];

if (hasRole(['guru'])) {
    $where[] = "ps.guru_id = ?";
    $params[] = $_SESSION['user_id'];
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Ambil data paket soal
$paket = $pdo->prepare("SELECT ps.*, mp.nama_mapel, k.nama_kelas, 
                       (SELECT COUNT(*) FROM bank_soal bs 
                        WHERE bs.mata_pelajaran_id = ps.mata_pelajaran_id 
                        AND bs.kelas_id = ps.kelas_id) as jumlah_soal_tersedia
                       FROM paket_soal ps
                       JOIN mata_pelajaran mp ON ps.mata_pelajaran_id = mp.id
                       JOIN kelas k ON ps.kelas_id = k.id
                       $where_clause
                       ORDER BY ps.tanggal_mulai DESC");
$paket->execute($params);
$paket = $paket->fetchAll();

// Set page title
$page_title = 'Manajemen Paket Soal';

include 'includes/header-modern.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manajemen Paket Soal</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Paket Soal</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Daftar Paket Soal</h3>
                            <button type="button" class="btn btn-primary float-right" data-bs-toggle="modal" data-bs-target="#tambahModal">
                                <i class="fas fa-plus"></i> Tambah Paket
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Nama Paket</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Kelas</th>
                                            <th>Jumlah Soal</th>
                                            <th>Durasi</th>
                                            <th>Periode</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paket as $index => $p): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($p['kode_paket']) ?></td>
                                            <td><?= htmlspecialchars($p['nama_paket']) ?></td>
                                            <td><?= htmlspecialchars($p['nama_mapel']) ?></td>
                                            <td><?= htmlspecialchars($p['nama_kelas']) ?></td>
                                            <td>
                                                <?= $p['jumlah_soal'] ?> 
                                                <small class="text-muted">(<?= $p['jumlah_soal_tersedia'] ?> tersedia)</small>
                                            </td>
                                            <td><?= $p['durasi_menit'] ?> menit</td>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($p['tanggal_mulai'])) ?><br>
                                                <?= date('d/m/Y H:i', strtotime($p['tanggal_selesai'])) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $now = date('Y-m-d H:i:s');
                                                $status = '';
                                                if ($now < $p['tanggal_mulai']) {
                                                    $status = '<span class="badge badge-warning">Belum Mulai</span>';
                                                } elseif ($now > $p['tanggal_selesai']) {
                                                    $status = '<span class="badge badge-secondary">Selesai</span>';
                                                } else {
                                                    $status = '<span class="badge badge-success">Aktif</span>';
                                                }
                                                echo $status;
                                                ?>
                                            </td>
                                            <td>
                                                <a href="jadwal_ujian.php?paket_id=<?= $p['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-calendar"></i> Jadwal
                                                </a>
                                                <button type="button" class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        data-bs-toggle="modal" data-bs-target="#hapusModal<?= $p['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal Edit -->
                                        <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Edit Paket Soal</h4>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Kode Paket</label>
                                                                        <input type="text" name="kode_paket" class="form-control" 
                                                                               value="<?= htmlspecialchars($p['kode_paket']) ?>" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Nama Paket</label>
                                                                        <input type="text" name="nama_paket" class="form-control" 
                                                                               value="<?= htmlspecialchars($p['nama_paket']) ?>" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Mata Pelajaran</label>
                                                                        <select name="mata_pelajaran_id" class="form-control" required>
                                                                            <?php foreach ($mata_pelajaran as $mp): ?>
                                                                                <option value="<?= $mp['id'] ?>" <?= $mp['id'] == $p['mata_pelajaran_id'] ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($mp['nama_mapel']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Kelas</label>
                                                                        <select name="kelas_id" class="form-control" required>
                                                                            <?php foreach ($kelas as $k): ?>
                                                                                <option value="<?= $k['id'] ?>" <?= $k['id'] == $p['kelas_id'] ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Jumlah Soal</label>
                                                                        <input type="number" name="jumlah_soal" class="form-control" 
                                                                               value="<?= $p['jumlah_soal'] ?>" min="1" max="<?= $p['jumlah_soal_tersedia'] ?>" required>
                                                                        <small class="text-muted">Maks: <?= $p['jumlah_soal_tersedia'] ?> soal tersedia</small>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Durasi (menit)</label>
                                                                        <input type="number" name="durasi_menit" class="form-control" 
                                                                               value="<?= $p['durasi_menit'] ?>" min="1" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Tanggal Mulai</label>
                                                                        <input type="datetime-local" name="tanggal_mulai" class="form-control" 
                                                                               value="<?= date('Y-m-d\TH:i', strtotime($p['tanggal_mulai'])) ?>" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Tanggal Selesai</label>
                                                                        <input type="datetime-local" name="tanggal_selesai" class="form-control" 
                                                                               value="<?= date('Y-m-d\TH:i', strtotime($p['tanggal_selesai'])) ?>" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="form-check">
                                                                        <input type="checkbox" name="acak_soal" class="form-check-input" 
                                                                               id="acak_soal<?= $p['id'] ?>" <?= $p['acak_soal'] ? 'checked' : '' ?>>
                                                                        <label class="form-check-label" for="acak_soal<?= $p['id'] ?>">
                                                                            Acak Urutan Soal
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input type="checkbox" name="acak_jawaban" class="form-check-input" 
                                                                               id="acak_jawaban<?= $p['id'] ?>" <?= $p['acak_jawaban'] ? 'checked' : '' ?>>
                                                                        <label class="form-check-label" for="acak_jawaban<?= $p['id'] ?>">
                                                                            Acak Urutan Jawaban
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input type="checkbox" name="tampilkan_nilai" class="form-check-input" 
                                                                               id="tampilkan_nilai<?= $p['id'] ?>" <?= $p['tampilkan_nilai'] ? 'checked' : '' ?>>
                                                                        <label class="form-check-label" for="tampilkan_nilai<?= $p['id'] ?>">
                                                                            Tampilkan Nilai Setelah Ujian
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="edit_paket" class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal Hapus -->
                                        <div class="modal fade" id="hapusModal<?= $p['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Konfirmasi Hapus</h4>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                            <p>Apakah Anda yakin ingin menghapus paket <strong><?= htmlspecialchars($p['nama_paket']) ?></strong>?</p>
                                                            <?php if ($p['jumlah_jadwal'] > 0): ?>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i>
                                                                    Paket ini memiliki <?= $p['jumlah_jadwal'] ?> jadwal ujian. 
                                                                    Hapus semua jadwal terlebih dahulu.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="hapus_paket" class="btn btn-danger"
                                                                    <?= $p['jumlah_jadwal'] > 0 ? 'disabled' : '' ?>>Hapus</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Tambah Paket -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Paket Soal Baru</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mata Pelajaran</label>
                                <select name="mata_pelajaran_id" class="form-control" required>
                                    <option value="">Pilih Mata Pelajaran</option>
                                    <?php foreach ($mata_pelajaran as $mp): ?>
                                        <option value="<?= $mp['id'] ?>"><?= htmlspecialchars($mp['nama_mapel']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kelas</label>
                                <select name="kelas_id" class="form-control" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php foreach ($kelas as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jumlah Soal</label>
                                <input type="number" name="jumlah_soal" class="form-control" min="1" max="100" required placeholder="Jumlah soal dalam paket">
                            </div>
                            <div class="form-group">
                                <label>Durasi (menit)</label>
                                <input type="number" name="durasi_menit" class="form-control" min="15" max="180" required placeholder="Durasi pengerjaan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Mulai</label>
                                <input type="datetime-local" name="tanggal_mulai" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Selesai</label>
                                <input type="datetime-local" name="tanggal_selesai" class="form-control" required>
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <strong>Info:</strong> Kode paket dan nama paket akan otomatis dibuat berdasarkan mata pelajaran, kelas, dan tanggal.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" name="acak_soal" class="form-check-input" id="acak_soal">
                                <label class="form-check-label" for="acak_soal">
                                    Acak Urutan Soal
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="acak_jawaban" class="form-check-input" id="acak_jawaban">
                                <label class="form-check-label" for="acak_jawaban">
                                    Acak Urutan Jawaban
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="tampilkan_nilai" class="form-check-input" id="tampilkan_nilai" checked>
                                <label class="form-check-label" for="tampilkan_nilai">
                                    Tampilkan Nilai Setelah Ujian
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_paket" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript untuk validasi dan enhancement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validasi form tambah paket
    const tambahForm = document.querySelector('#tambahModal form');
    if (tambahForm) {
        tambahForm.addEventListener('submit', function(e) {
            const mataPelajaranId = this.mata_pelajaran_id.value;
            const kelasId = this.kelas_id.value;
            const jumlahSoal = parseInt(this.jumlah_soal.value);
            const durasiMenit = parseInt(this.durasi_menit.value);
            const tanggalMulai = this.tanggal_mulai.value;
            const tanggalSelesai = this.tanggal_selesai.value;
            
            if (!mataPelajaranId || !kelasId || !jumlahSoal || !durasiMenit || !tanggalMulai || !tanggalSelesai) {
                e.preventDefault();
                alert('Semua field harus diisi!');
                return false;
            }
            
            if (jumlahSoal < 1 || jumlahSoal > 100) {
                e.preventDefault();
                alert('Jumlah soal harus antara 1-100!');
                return false;
            }
            
            if (durasiMenit < 15 || durasiMenit > 180) {
                e.preventDefault();
                alert('Durasi harus antara 15-180 menit!');
                return false;
            }
            
            // Validasi tanggal
            const mulaiDate = new Date(tanggalMulai);
            const selesaiDate = new Date(tanggalSelesai);
            const now = new Date();
            
            if (mulaiDate >= selesaiDate) {
                e.preventDefault();
                alert('Tanggal selesai harus lebih besar dari tanggal mulai!');
                return false;
            }
            
            if (selesaiDate <= now) {
                e.preventDefault();
                alert('Tanggal selesai harus lebih besar dari waktu sekarang!');
                return false;
            }
        });
    }
    
    // Auto-focus pada input mata pelajaran saat modal dibuka
    const tambahModal = document.getElementById('tambahModal');
    if (tambahModal) {
        tambahModal.addEventListener('shown.bs.modal', function() {
            this.querySelector('select[name="mata_pelajaran_id"]').focus();
        });
    }
});
</script>

<?php include 'includes/footer-modern.php'; ?>