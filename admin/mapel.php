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
    if (isset($_POST['tambah_mapel'])) {
        $kode_mapel = $_POST['kode_mapel'];
        $nama_mapel = $_POST['nama_mapel'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel, nama_mapel) VALUES (?, ?)");
            $stmt->execute([$kode_mapel, $nama_mapel]);
            
            $_SESSION['success'] = 'Mata pelajaran berhasil ditambahkan';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Kode mata pelajaran sudah ada';
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

// Ambil data mata pelajaran
$mapel = $pdo->query("SELECT m.*, COUNT(b.id) as jumlah_soal 
                      FROM mata_pelajaran m 
                      LEFT JOIN bank_soal b ON m.id = b.mata_pelajaran_id 
                      GROUP BY m.id 
                      ORDER BY m.kode_mapel")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Mata Pelajaran</h3>
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#tambahModal">
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
                                                data-toggle="modal" data-target="#editModal<?= $m['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-toggle="modal" data-target="#hapusModal<?= $m['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal Edit -->
                                <div class="modal fade" id="editModal<?= $m['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Edit Mata Pelajaran</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
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
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_mapel" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Hapus -->
                                <div class="modal fade" id="hapusModal<?= $m['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
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
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
<div class="modal fade" id="tambahModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Mata Pelajaran Baru</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Mapel</label>
                        <input type="text" name="kode_mapel" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_mapel" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>