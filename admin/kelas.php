<?php
// Pastikan session dimulai sebelum semua operasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';
require_once '../config/database.php';

// Cek login dan role
checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Proses form
if ($_POST) {
    if (isset($_POST['tambah_kelas'])) {
        $nama_kelas = $_POST['nama_kelas'];
        $tingkat = $_POST['tingkat'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, tingkat) VALUES (?, ?)");
            $stmt->execute([$nama_kelas, $tingkat]);
            
            $_SESSION['success'] = 'Kelas berhasil ditambahkan';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Nama kelas sudah ada';
        }
        
        header('Location: kelas.php');
        exit;
    }
    
    if (isset($_POST['edit_kelas'])) {
        $id = $_POST['id'];
        $nama_kelas = $_POST['nama_kelas'];
        $tingkat = $_POST['tingkat'];
        
        $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, tingkat = ? WHERE id = ?");
        $stmt->execute([$nama_kelas, $tingkat, $id]);
        
        $_SESSION['success'] = 'Kelas berhasil diperbarui';
        header('Location: kelas.php');
        exit;
    }
    
    if (isset($_POST['hapus_kelas'])) {
        $id = $_POST['id'];
        
        // Cek apakah ada peserta di kelas ini
        $check = $pdo->prepare("SELECT COUNT(*) FROM peserta WHERE kelas_id = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus kelas yang memiliki peserta';
        } else {
            $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Kelas berhasil dihapus';
        }
        
        header('Location: kelas.php');
        exit;
    }
}

// Ambil data kelas
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY tingkat, nama_kelas")->fetchAll();

$page_title = 'Manajemen Kelas';
include 'includes/header-modern.php';
?>

<!-- Page Header -->
<div class="content-header">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-door-open text-gradient me-2"></i>
                Manajemen Kelas
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Kelas</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahKelasModal">
                <i class="fas fa-plus me-1"></i>Tambah Kelas
            </button>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-primary"><?php echo count($kelas); ?></div>
                <div class="small text-muted">Total Kelas</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-success">
                    <?php echo count(array_filter($kelas, function($k) { return $k['tingkat'] <= 6; })); ?>
                </div>
                <div class="small text-muted">SD</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-info">
                    <?php echo count(array_filter($kelas, function($k) { return $k['tingkat'] > 6; })); ?>
                </div>
                <div class="small text-muted">SMP/SMA</div>
            </div>
        </div>
    </div>
</div>

<!-- Classes Table -->
<div class="card border-0 shadow-custom">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="fas fa-table text-gradient me-2"></i>
            Daftar Kelas
        </h5>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width: 200px;">
                <input type="text" name="table_search" class="form-control" placeholder="Cari kelas...">
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Jumlah Peserta</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kelas)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>Belum ada data kelas</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kelas as $index => $k): ?>
                            <?php
                            // Hitung jumlah peserta per kelas
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM peserta WHERE kelas_id = ?");
                            $stmt->execute([$k['id']]);
                            $jumlah_peserta = $stmt->fetchColumn();
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($k['nama_kelas']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $k['tingkat'] <= 6 ? 'success' : 'info'; ?>">
                                        Kelas <?php echo $k['tingkat']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary rounded-pill">
                                        <?php echo $jumlah_peserta; ?> peserta
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editKelas(<?php echo $k['id']; ?>, '<?php echo htmlspecialchars($k['nama_kelas']); ?>', <?php echo $k['tingkat']; ?>)"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteKelas(<?php echo $k['id']; ?>, '<?php echo htmlspecialchars($k['nama_kelas']); ?>', <?php echo $jumlah_peserta; ?>)"
                                                title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Kelas -->
<div class="modal fade" id="tambahKelasModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Tambah Kelas Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_kelas" class="form-label">Nama Kelas</label>
                        <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" required>
                        <small class="form-text text-muted">Contoh: X IPA 1, VII A, 5A</small>
                    </div>
                    <div class="mb-3">
                        <label for="tingkat" class="form-label">Tingkat</label>
                        <select class="form-select" id="tingkat" name="tingkat" required>
                            <option value="">Pilih Tingkat</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>">Kelas <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kelas" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kelas -->
<div class="modal fade" id="editKelasModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Edit Kelas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nama_kelas" class="form-label">Nama Kelas</label>
                        <input type="text" class="form-control" id="edit_nama_kelas" name="nama_kelas" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tingkat" class="form-label">Tingkat</label>
                        <select class="form-select" id="edit_tingkat" name="tingkat" required>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>">Kelas <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_kelas" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Class Management Scripts -->
<script>
    // Class management functions
    function editKelas(id, namaKelas, tingkat) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama_kelas').value = namaKelas;
        document.getElementById('edit_tingkat').value = tingkat;
        
        const modal = new bootstrap.Modal(document.getElementById('editKelasModal'));
        modal.show();
    }
    
    function deleteKelas(id, namaKelas, jumlahPeserta) {
        if (jumlahPeserta > 0) {
            Utils.showWarning(`Kelas "${namaKelas}" memiliki ${jumlahPeserta} peserta. Tidak dapat dihapus.`, 'Tidak Dapat Dihapus');
            return;
        }
        
        Utils.confirm(`Apakah Anda yakin ingin menghapus kelas "${namaKelas}"?`, 'Hapus Kelas').then((result) => {
            if (result.isConfirmed) {
                Utils.showLoading('Menghapus kelas...');
                setTimeout(() => {
                    Utils.hideLoading();
                    Utils.showSuccess('Kelas berhasil dihapus!', 'Success');
                }, 1000);
            }
        });
    }
    
    // Table search functionality
    document.querySelector('input[name="table_search"]').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    // Form validation
    document.querySelector('#tambahKelasModal form').addEventListener('submit', function(e) {
        const namaKelas = this.nama_kelas.value.trim();
        const tingkat = this.tingkat.value;
        
        if (!namaKelas || !tingkat) {
            e.preventDefault();
            Utils.showWarning('Semua field harus diisi!', 'Validasi Gagal');
            return false;
        }
        
        if (namaKelas.length < 2) {
            e.preventDefault();
            Utils.showWarning('Nama kelas minimal 2 karakter!', 'Validasi Gagal');
            return false;
        }
    });
</script>

<?php include 'includes/footer-modern.php'; ?>