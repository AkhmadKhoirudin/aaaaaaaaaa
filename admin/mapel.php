<?php
// Pastikan session dimulai sebelum semua operasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Proses form
if ($_POST) {
    if (isset($_POST['tambah_mapel'])) {
        $nama_mapel = $_POST['nama_mapel'];
        
        // Generate kode mapel otomatis dari nama mata pelajaran
        $kode_mapel = generateKodeMapel($nama_mapel);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel, nama_mapel) VALUES (?, ?)");
            $stmt->execute([$kode_mapel, $nama_mapel]);
            
            $_SESSION['success'] = 'Mata pelajaran berhasil ditambahkan dengan kode: ' . $kode_mapel;
        } catch (PDOException $e) {
            // Jika kode sudah ada, coba dengan kode alternatif
            $kode_mapel = generateKodeMapelAlternatif($nama_mapel);
            try {
                $stmt = $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel, nama_mapel) VALUES (?, ?)");
                $stmt->execute([$kode_mapel, $nama_mapel]);
                
                $_SESSION['success'] = 'Mata pelajaran berhasil ditambahkan dengan kode: ' . $kode_mapel;
            } catch (PDOException $e2) {
                $_SESSION['error'] = 'Gagal menambahkan mata pelajaran. Silakan coba lagi.';
            }
        }
        
        header('Location: mapel.php');
        exit;
    }
    
    if (isset($_POST['edit_mapel'])) {
        $id = $_POST['id'];
        $kode_mapel = $_POST['kode_mapel'];
        $nama_mapel = $_POST['nama_mapel'];
        
        try {
            $stmt = $pdo->prepare("UPDATE mata_pelajaran SET kode_mapel = ?, nama_mapel = ? WHERE id = ?");
            $stmt->execute([$kode_mapel, $nama_mapel, $id]);
            
            $_SESSION['success'] = 'Mata pelajaran berhasil diperbarui';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Kode mata pelajaran sudah ada';
        }
        
        header('Location: mapel.php');
        exit;
    }
    
    if (isset($_POST['hapus_mapel'])) {
        $id = $_POST['id'];
        
        // Cek apakah ada soal yang menggunakan mapel ini
        $check = $pdo->prepare("SELECT COUNT(*) FROM bank_soal WHERE mata_pelajaran_id = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus mata pelajaran yang masih memiliki soal';
        } else {
            $stmt = $pdo->prepare("DELETE FROM mata_pelajaran WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Mata pelajaran berhasil dihapus';
        }
        
        header('Location: mapel.php');
        exit;
    }
}

// Fungsi untuk generate kode mapel otomatis
function generateKodeMapel($nama_mapel) {
    global $pdo;
    
    // Ambil 3 huruf pertama dari nama mapel dan ubah menjadi uppercase
    $kode = strtoupper(substr(str_replace(' ', '', $nama_mapel), 0, 3));
    
    // Jika kurang dari 3 huruf, tambahkan angka
    if (strlen($kode) < 3) {
        $kode .= str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
    }
    
    // Cek apakah kode sudah ada di database
    $check = $pdo->prepare("SELECT COUNT(*) FROM mata_pelajaran WHERE kode_mapel = ?");
    $check->execute([$kode]);
    $count = $check->fetchColumn();
    
    // Jika kode sudah ada, gunakan fungsi alternatif
    if ($count > 0) {
        return generateKodeMapelAlternatif($nama_mapel);
    }
    
    return $kode;
}

// Fungsi untuk generate kode mapel alternatif jika kode pertama sudah ada
function generateKodeMapelAlternatif($nama_mapel) {
    global $pdo;
    
    // Ambil 3 huruf pertama dari nama mapel
    $base_kode = strtoupper(substr(str_replace(' ', '', $nama_mapel), 0, 3));
    
    // Coba tambahkan angka 1-99
    for ($i = 1; $i <= 99; $i++) {
        $kode = $base_kode . str_pad($i, 2, '0', STR_PAD_LEFT);
        
        // Cek apakah kode sudah ada
        $check = $pdo->prepare("SELECT COUNT(*) FROM mata_pelajaran WHERE kode_mapel = ?");
        $check->execute([$kode]);
        $count = $check->fetchColumn();
        
        if ($count == 0) {
            return $kode;
        }
    }
    
    // Jika masih tidak ada, tambahkan timestamp
    return $base_kode . date('is');
}

// Ambil data mata pelajaran
$mapel = $pdo->query("SELECT m.*, COUNT(b.id) as jumlah_soal 
                      FROM mata_pelajaran m 
                      LEFT JOIN bank_soal b ON m.id = b.mata_pelajaran_id 
                      GROUP BY m.id 
                      ORDER BY m.kode_mapel")->fetchAll();

// Set page title
$page_title = 'Manajemen Mata Pelajaran';

include 'includes/header-modern.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manajemen Mata Pelajaran</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Mata Pelajaran</li>
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Mata Pelajaran</h3>
                    <button type="button" class="btn btn-primary float-right" data-bs-toggle="modal" data-bs-target="#tambahModal">
                        <i class="fas fa-plus"></i> Tambah Mapel
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Mapel</th>
                                    <th>Nama Mata Pelajaran</th>
                                    <th>Jumlah Soal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mapel as $index => $m): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($m['kode_mapel']) ?></td>
                                    <td><?= htmlspecialchars($m['nama_mapel']) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= $m['jumlah_soal'] ?> soal</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal" data-bs-target="#editModal<?= $m['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#hapusModal<?= $m['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal Edit -->
                                <div class="modal fade" id="editModal<?= $m['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Edit Mata Pelajaran</h4>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <div class="form-group">
                                                        <label>Kode Mapel</label>
                                                        <input type="text" name="kode_mapel" class="form-control" 
                                                               value="<?= htmlspecialchars($m['kode_mapel']) ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Nama Mata Pelajaran</label>
                                                        <input type="text" name="nama_mapel" class="form-control" 
                                                               value="<?= htmlspecialchars($m['nama_mapel']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_mapel" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Hapus -->
                                <div class="modal fade" id="hapusModal<?= $m['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <p>Apakah Anda yakin ingin menghapus mata pelajaran <strong><?= htmlspecialchars($m['nama_mapel']) ?></strong>?</p>
                                                    <?php if ($m['jumlah_soal'] > 0): ?>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            Mata pelajaran ini memiliki <?= $m['jumlah_soal'] ?> soal. 
                                                            Hapus semua soal terlebih dahulu.
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="hapus_mapel" class="btn btn-danger"
                                                            <?= $m['jumlah_soal'] > 0 ? 'disabled' : '' ?>>Hapus</button>
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
    </section>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Mata Pelajaran Baru</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" class="form-control" required minlength="3" placeholder="Masukkan nama mata pelajaran">
                        <small class="form-text text-muted">Kode mapel akan otomatis dibuat dari nama mata pelajaran</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_mapel" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript untuk validasi dan enhancement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validasi form tambah mata pelajaran
    const tambahForm = document.querySelector('#tambahModal form');
    if (tambahForm) {
        tambahForm.addEventListener('submit', function(e) {
            const namaMapel = this.nama_mapel.value.trim();
            
            if (!namaMapel) {
                e.preventDefault();
                alert('Nama Mata Pelajaran harus diisi!');
                return false;
            }
            
            if (namaMapel.length < 3) {
                e.preventDefault();
                alert('Nama Mata Pelajaran minimal 3 karakter!');
                return false;
            }
        });
    }
    
    // Auto-focus pada input nama mata pelajaran saat modal dibuka
    const tambahModal = document.getElementById('tambahModal');
    if (tambahModal) {
        tambahModal.addEventListener('shown.bs.modal', function() {
            this.querySelector('input[name="nama_mapel"]').focus();
        });
    }
});
</script>

<?php include 'includes/footer-modern.php'; ?>