<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Proses form
if ($_POST) {
    if (isset($_POST['tambah_ruang'])) {
        $kode_ruang = $_POST['kode_ruang'];
        $nama_ruang = $_POST['nama_ruang'];
        $kapasitas = $_POST['kapasitas'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO ruang_ujian (kode_ruang, nama_ruang, kapasitas) VALUES (?, ?, ?)");
            $stmt->execute([$kode_ruang, $nama_ruang, $kapasitas]);
            
            $_SESSION['success'] = 'Ruang ujian berhasil ditambahkan';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Kode ruang sudah ada';
        }
        
        header('Location: ruang.php');
        exit;
    }
    
    if (isset($_POST['edit_ruang'])) {
        $id = $_POST['id'];
        $kode_ruang = $_POST['kode_ruang'];
        $nama_ruang = $_POST['nama_ruang'];
        $kapasitas = $_POST['kapasitas'];
        
        try {
            $stmt = $pdo->prepare("UPDATE ruang_ujian SET kode_ruang = ?, nama_ruang = ?, kapasitas = ? WHERE id = ?");
            $stmt->execute([$kode_ruang, $nama_ruang, $kapasitas, $id]);
            
            $_SESSION['success'] = 'Ruang ujian berhasil diperbarui';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Kode ruang sudah ada';
        }
        
        header('Location: ruang.php');
        exit;
    }
    
    if (isset($_POST['hapus_ruang'])) {
        $id = $_POST['id'];
        
        // Cek apakah ada jadwal ujian di ruang ini
        $check = $pdo->prepare("SELECT COUNT(*) FROM jadwal_ujian WHERE ruang_ujian_id = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus ruang yang masih memiliki jadwal ujian';
        } else {
            $stmt = $pdo->prepare("DELETE FROM ruang_ujian WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Ruang ujian berhasil dihapus';
        }
        
        header('Location: ruang.php');
        exit;
    }
}

// Ambil data ruang ujian
$ruang = $pdo->query("SELECT r.*, COUNT(j.id) as jumlah_jadwal 
                      FROM ruang_ujian r 
                      LEFT JOIN jadwal_ujian j ON r.id = j.ruang_ujian_id 
                      GROUP BY r.id 
                      ORDER BY r.kode_ruang")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manajemen Ruang Ujian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Ruang Ujian</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Statistik Ruang</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-door-open"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Ruang</span>
                                    <span class="info-box-number"><?= count($ruang) ?></span>
                                </div>
                            </div>
                            <?php
                            $total_kapasitas = array_sum(array_column($ruang, 'kapasitas'));
                            ?>
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Kapasitas</span>
                                    <span class="info-box-number"><?= $total_kapasitas ?> peserta</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Daftar Ruang Ujian</h3>
                            <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#tambahModal">
                                <i class="fas fa-plus"></i> Tambah Ruang
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode Ruang</th>
                                            <th>Nama Ruang</th>
                                            <th>Kapasitas</th>
                                            <th>Jadwal Ujian</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ruang as $index => $r): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($r['kode_ruang']) ?></td>
                                            <td><?= htmlspecialchars($r['nama_ruang']) ?></td>
                                            <td>
                                                <span class="badge badge-primary"><?= $r['kapasitas'] ?> peserta</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info"><?= $r['jumlah_jadwal'] ?> jadwal</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        data-toggle="modal" data-target="#editModal<?= $r['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-toggle="modal" data-target="#hapusModal<?= $r['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal Edit -->
                                        <div class="modal fade" id="editModal<?= $r['id'] ?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Edit Ruang Ujian</h4>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                            <div class="form-group">
                                                                <label>Kode Ruang</label>
                                                                <input type="text" name="kode_ruang" class="form-control" 
                                                                       value="<?= htmlspecialchars($r['kode_ruang']) ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Nama Ruang</label>
                                                                <input type="text" name="nama_ruang" class="form-control" 
                                                                       value="<?= htmlspecialchars($r['nama_ruang']) ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Kapasitas</label>
                                                                <input type="number" name="kapasitas" class="form-control" 
                                                                       value="<?= $r['kapasitas'] ?>" min="1" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                            <button type="submit" name="edit_ruang" class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal Hapus -->
                                        <div class="modal fade" id="hapusModal<?= $r['id'] ?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Konfirmasi Hapus</h4>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                            <p>Apakah Anda yakin ingin menghapus ruang <strong><?= htmlspecialchars($r['nama_ruang']) ?></strong>?</p>
                                                            <?php if ($r['jumlah_jadwal'] > 0): ?>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i>
                                                                    Ruang ini memiliki <?= $r['jumlah_jadwal'] ?> jadwal ujian. 
                                                                    Hapus semua jadwal terlebih dahulu.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                            <button type="submit" name="hapus_ruang" class="btn btn-danger" 
                                                                    <?= $r['jumlah_jadwal'] > 0 ? 'disabled' : '' ?>>Hapus</button>
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

<!-- Modal Tambah -->
<div class="modal fade" id="tambahModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Ruang Ujian Baru</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Ruang</label>
                        <input type="text" name="kode_ruang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Ruang</label>
                        <input type="text" name="nama_ruang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kapasitas</label>
                        <input type="number" name="kapasitas" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_ruang" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>