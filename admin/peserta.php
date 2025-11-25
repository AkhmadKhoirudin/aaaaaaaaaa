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
    if (isset($_POST['tambah_peserta'])) {
        $nis = $_POST['nis'];
        $nisn = $_POST['nisn'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $kelas_id = $_POST['kelas_id'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $alamat = $_POST['alamat'];
        $no_hp = $_POST['no_hp'];
        $nama_wali = $_POST['nama_wali'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        
        try {
            // Insert ke tabel users
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (?, ?, ?, ?, 'peserta')");
            $stmt->execute([$nis, $password, $nama_lengkap, '']);
            $user_id = $pdo->lastInsertId();
            
            // Insert ke tabel peserta
            $stmt = $pdo->prepare("INSERT INTO peserta (user_id, nis, nisn, kelas_id, jenis_kelamin, tanggal_lahir, alamat, no_hp, nama_wali) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $nis, $nisn, $kelas_id, $jenis_kelamin, $tanggal_lahir, $alamat, $no_hp, $nama_wali]);
            
            $_SESSION['success'] = 'Peserta berhasil ditambahkan';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'NIS sudah ada';
        }
        
        header('Location: peserta.php');
        exit;
    }
    
    if (isset($_POST['edit_peserta'])) {
        $id = $_POST['id'];
        $nis = $_POST['nis'];
        $nisn = $_POST['nisn'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $kelas_id = $_POST['kelas_id'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $alamat = $_POST['alamat'];
        $no_hp = $_POST['no_hp'];
        $nama_wali = $_POST['nama_wali'];
        
        try {
            // Update tabel users
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ? WHERE id = (SELECT user_id FROM peserta WHERE id = ?)");
            $stmt->execute([$nama_lengkap, $id]);
            
            // Update tabel peserta
            $stmt = $pdo->prepare("UPDATE peserta SET nis = ?, nisn = ?, kelas_id = ?, jenis_kelamin = ?, 
                                  tanggal_lahir = ?, alamat = ?, no_hp = ?, nama_wali = ? WHERE id = ?");
            $stmt->execute([$nis, $nisn, $kelas_id, $jenis_kelamin, $tanggal_lahir, $alamat, $no_hp, $nama_wali, $id]);
            
            $_SESSION['success'] = 'Peserta berhasil diperbarui';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'NIS sudah ada';
        }
        
        header('Location: peserta.php');
        exit;
    }
    
    if (isset($_POST['hapus_peserta'])) {
        $id = $_POST['id'];
        
        // Cek apakah peserta ini sudah pernah ujian
        $check = $pdo->prepare("SELECT COUNT(*) FROM sesi_ujian WHERE peserta_id = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus peserta yang sudah pernah ujian';
        } else {
            // Hapus dari tabel peserta dan users
            $stmt = $pdo->prepare("DELETE FROM peserta WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = 'Peserta berhasil dihapus';
        }
        
        header('Location: peserta.php');
        exit;
    }
}

// Ambil data kelas untuk dropdown
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

// Ambil data peserta dengan join ke kelas
$peserta = $pdo->query("SELECT p.*, k.nama_kelas, u.username, u.nama_lengkap, u.is_active
                       FROM peserta p
                       JOIN kelas k ON p.kelas_id = k.id
                       JOIN users u ON p.user_id = u.id
                       ORDER BY k.nama_kelas, u.nama_lengkap")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manajemen Peserta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Peserta</li>
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
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= count($peserta) ?></h3>
                            <p>Total Peserta</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= count(array_filter($peserta, fn($p) => $p['is_active'])) ?></h3>
                            <p>Peserta Aktif</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= count(array_unique(array_column($peserta, 'kelas_id'))) ?></h3>
                            <p>Kelas</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-school"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= count(array_filter($peserta, fn($p) => !$p['is_active'])) ?></h3>
                            <p>Nonaktif</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Peserta</h3>
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#tambahModal">
                        <i class="fas fa-plus"></i> Tambah Peserta
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>NISN</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($peserta as $index => $p): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($p['nis']) ?></td>
                                    <td><?= htmlspecialchars($p['nisn']) ?></td>
                                    <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($p['nama_kelas']) ?></td>
                                    <td><?= $p['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                    <td>
                                        <span class="badge badge-<?= $p['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-toggle="modal" data-target="#editModal<?= $p['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-toggle="modal" data-target="#hapusModal<?= $p['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal Edit -->
                                <div class="modal fade" id="editModal<?= $p['id'] ?>">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Edit Peserta</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>NIS</label>
                                                                <input type="text" name="nis" class="form-control" 
                                                                       value="<?= htmlspecialchars($p['nis']) ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>NISN</label>
                                                                <input type="text" name="nisn" class="form-control" 
                                                                       value="<?= htmlspecialchars($p['nisn']) ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Nama Lengkap</label>
                                                                <input type="text" name="nama_lengkap" class="form-control" 
                                                                       value="<?= htmlspecialchars($p['nama_lengkap']) ?>" required>
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
                                                                <label>Jenis Kelamin</label>
                                                                <select name="jenis_kelamin" class="form-control" required>
                                                                    <option value="L" <?= $p['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                                                    <option value="P" <?= $p['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Tanggal Lahir</label>
                                                                <input type="date" name="tanggal_lahir" class="form-control" 
                                                                       value="<?= $p['tanggal_lahir'] ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>No HP</label>
                                                                <input type="text" name="no_hp" class="form-control" 
                                                                       value="<?= htmlspecialchars($p['no_hp']) ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Nama Wali</label>
                                                                <input type="text" name="nama_wali" class="form-control" 
                                                                       value="<?= htmlspecialchars($p['nama_wali']) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Alamat</label>
                                                        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($p['alamat']) ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_peserta" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Hapus -->
                                <div class="modal fade" id="hapusModal<?= $p['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <p>Apakah Anda yakin ingin menghapus peserta <strong><?= htmlspecialchars($p['nama_lengkap']) ?></strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" name="hapus_peserta" class="btn btn-danger">Hapus</button>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Peserta Baru</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NIS</label>
                                <input type="text" name="nis" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>NISN</label>
                                <input type="text" name="nisn" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" required>
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
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-control" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>No HP</label>
                                <input type="text" name="no_hp" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Nama Wali</label>
                                <input type="text" name="nama_wali" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_peserta" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>