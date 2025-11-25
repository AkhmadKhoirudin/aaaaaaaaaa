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
    if (isset($_POST['tambah_kelas'])) {
        $nama_kelas = $_POST['nama_kelas'];
        $jurusan = $_POST['jurusan'];
        
        $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, jurusan) VALUES (?, ?)");
        $stmt->execute([$nama_kelas, $jurusan]);
        
        $_SESSION['success'] = 'Kelas berhasil ditambahkan';
        header('Location: kelas.php');
        exit;
    }
    
    if (isset($_POST['edit_kelas'])) {
        $id = $_POST['id'];
        $nama_kelas = $_POST['nama_kelas'];
        $jurusan = $_POST['jurusan'];
        
        $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, jurusan = ? WHERE id = ?");
        $stmt->execute([$nama_kelas, $jurusan, $id]);
        
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
            $_SESSION['error'] = 'Tidak dapat menghapus kelas yang masih memiliki peserta';
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
$kelas = $pdo->query("SELECT k.*, COUNT(p.id) as jumlah_peserta 
                      FROM kelas k 
                      LEFT JOIN peserta p ON k.id = p.kelas_id 
                      GROUP BY k.id 
                      ORDER BY k.nama_kelas")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manajemen Kelas</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Kelas</li>
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
                    <h3 class="card-title">Daftar Kelas</h3>
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#tambahModal">
                        <i class="fas fa-plus"></i> Tambah Kelas
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kelas</th>
                                    <th>Jurusan</th>
                                    <th>Jumlah Peserta</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kelas as $index => $k): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($k['nama_kelas']) ?></td>
                                    <td><?= htmlspecialchars($k['jurusan']) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= $k['jumlah_peserta'] ?> peserta</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-toggle="modal" data-target="#editModal<?= $k['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-toggle="modal" data-target="#hapusModal<?= $k['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal Edit -->
                                <div class="modal fade" id="editModal<?= $k['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Edit Kelas</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                    <div class="form-group">
                                                        <label>Nama Kelas</label>
                                                        <input type="text" name="nama_kelas" class="form-control" 
                                                               value="<?= htmlspecialchars($k['nama_kelas']) ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Jurusan</label>
                                                        <input type="text" name="jurusan" class="form-control" 
                                                               value="<?= htmlspecialchars($k['jurusan']) ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_kelas" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Hapus -->
                                <div class="modal fade" id="hapusModal<?= $k['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                    <p>Apakah Anda yakin ingin menghapus kelas <strong><?= htmlspecialchars($k['nama_kelas']) ?></strong>?</p>
                                                    <?php if ($k['jumlah_peserta'] > 0): ?>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            Kelas ini memiliki <?= $k['jumlah_peserta'] ?> peserta. 
                                                            Pindahkan peserta ke kelas lain terlebih dahulu.
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" name="hapus_kelas" class="btn btn-danger" 
                                                            <?= $k['jumlah_peserta'] > 0 ? 'disabled' : '' ?>>Hapus</button>
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
                    <h4 class="modal-title">Tambah Kelas Baru</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Jurusan</label>
                        <input type="text" name="jurusan" class="form-control" 
                               placeholder="Contoh: Teknik Komputer dan Jaringan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kelas" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>