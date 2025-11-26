<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator', 'guru'])) {
    header('Location: dashboard.php');
    exit;
}

// Proses form
if ($_POST) {
    if (isset($_POST['tambah_soal'])) {
        $mata_pelajaran_id = $_POST['mata_pelajaran_id'];
        $kelas_id = $_POST['kelas_id'];
        $guru_id = $_SESSION['user_id'];
        $jenis_soal = $_POST['jenis_soal'];
        $pertanyaan = $_POST['pertanyaan'];
        $pilihan_a = $_POST['pilihan_a'];
        $pilihan_b = $_POST['pilihan_b'];
        $pilihan_c = $_POST['pilihan_c'];
        $pilihan_d = $_POST['pilihan_d'];
        $pilihan_e = $_POST['pilihan_e'];
        $jawaban_benar = $_POST['jawaban_benar'];
        $bobot_soal = $_POST['bobot_soal'];
        $tingkat_kesulitan = $_POST['tingkat_kesulitan'];
        
        // Handle upload gambar
        $gambar = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $uploadDir = '../uploads/soal/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar = 'soal_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadDir . $gambar);
        }
        
        $stmt = $pdo->prepare("INSERT INTO bank_soal
                                      (mata_pelajaran_id, kelas_id, guru_id, jenis_soal, pertanyaan, gambar,
                                       pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar,
                                       bobot_soal, tingkat_kesulitan)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$mata_pelajaran_id, $kelas_id, $guru_id, $jenis_soal, $pertanyaan, $gambar,
                       $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $pilihan_e, $jawaban_benar,
                       $bobot_soal, $tingkat_kesulitan]);
        
        $_SESSION['success'] = 'Soal berhasil ditambahkan';
        header('Location: bank_soal.php');
        exit;
    }
     
    if (isset($_POST['edit_soal'])) {
        $id = $_POST['id'];
        $mata_pelajaran_id = $_POST['mata_pelajaran_id'];
        $kelas_id = $_POST['kelas_id'];
        $pertanyaan = $_POST['pertanyaan'];
        $pilihan_a = $_POST['pilihan_a'];
        $pilihan_b = $_POST['pilihan_b'];
        $pilihan_c = $_POST['pilihan_c'];
        $pilihan_d = $_POST['pilihan_d'];
        $pilihan_e = $_POST['pilihan_e'];
        $jawaban_benar = $_POST['jawaban_benar'];
        $bobot_soal = $_POST['bobot_soal'];
        $tingkat_kesulitan = $_POST['tingkat_kesulitan'];
        
        // Handle upload gambar baru
        $gambar_update = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $uploadDir = '../uploads/soal/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar_update = 'soal_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadDir . $gambar_update);
            
            // Hapus gambar lama
            $old_gambar = $pdo->prepare("SELECT gambar FROM bank_soal WHERE id = ?");
            $old_gambar->execute([$id]);
            $old_file = $old_gambar->fetchColumn();
            if ($old_file && file_exists($uploadDir . $old_file)) {
                unlink($uploadDir . $old_file);
            }
        }
        
        $sql = "UPDATE bank_soal SET
                        mata_pelajaran_id = ?, kelas_id = ?, pertanyaan = ?,
                        pilihan_a = ?, pilihan_b = ?, pilihan_c = ?, pilihan_d = ?, pilihan_e = ?,
                        jawaban_benar = ?, bobot_soal = ?, tingkat_kesulitan = ?";
        
        $params = [$mata_pelajaran_id, $kelas_id, $pertanyaan,
                   $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $pilihan_e,
                   $jawaban_benar, $bobot_soal, $tingkat_kesulitan];
        
        if ($gambar_update) {
            $sql .= ", gambar = ?";
            $params[] = $gambar_update;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['success'] = 'Soal berhasil diperbarui';
        header('Location: bank_soal.php');
        exit;
    }
    
    if (isset($_POST['hapus_soal'])) {
        $id = $_POST['id'];
        
        // Hapus gambar jika ada
        $gambar = $pdo->prepare("SELECT gambar FROM bank_soal WHERE id = ?");
        $gambar->execute([$id]);
        $file = $gambar->fetchColumn();
        if ($file && file_exists('../uploads/soal/' . $file)) {
            unlink('../uploads/soal/' . $file);
        }
        
        $stmt = $pdo->prepare("DELETE FROM bank_soal WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = 'Soal berhasil dihapus';
        header('Location: bank_soal.php');
        exit;
    }
}

// Ambil data untuk filter
$mapel = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY nama_mapel")->fetchAll();
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

// Filter soal
$where = [];
$params = [];

if (hasRole(['guru'])) {
    $where[] = "bs.guru_id = ?";
    $params[] = $_SESSION['user_id'];
}

if (isset($_GET['mapel']) && $_GET['mapel']) {
    $where[] = "bs.mata_pelajaran_id = ?";
    $params[] = $_GET['mapel'];
}

if (isset($_GET['kelas']) && $_GET['kelas']) {
    $where[] = "bs.kelas_id = ?";
    $params[] = $_GET['kelas'];
}

if (isset($_GET['jenis']) && $_GET['jenis']) {
    $where[] = "bs.jenis_soal = ?";
    $params[] = $_GET['jenis'];
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Ambil data soal dengan pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$total = $pdo->prepare("SELECT COUNT(*) FROM bank_soal bs
                       JOIN mata_pelajaran mp ON bs.mata_pelajaran_id = mp.id
                       JOIN kelas k ON bs.kelas_id = k.id
                       $where_clause");
$total->execute($params);
$total_rows = $total->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$soal = $pdo->prepare("SELECT bs.*, mp.nama_mapel, k.nama_kelas, u.nama_lengkap as guru
                      FROM bank_soal bs
                      JOIN mata_pelajaran mp ON bs.mata_pelajaran_id = mp.id
                      JOIN kelas k ON bs.kelas_id = k.id
                      JOIN users u ON bs.guru_id = u.id
                      $where_clause
                      ORDER BY bs.id DESC
                      LIMIT $per_page OFFSET $offset");
$soal->execute($params);
$soal = $soal->fetchAll();

// Set page title
$page_title = 'Bank Soal';

include 'includes/header-modern.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bank Soal</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#tambahModal">
                            <i class="fas fa-plus"></i> Tambah Soal
                        </button>
                    </div>
                </div>
            </div>
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

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Soal</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="form-inline">
                                <div class="form-group mr-3 mb-2">
                                    <label class="mr-2">Mata Pelajaran:</label>
                                    <select name="mapel" class="form-control form-control-sm">
                                        <option value="">Semua</option>
                                        <?php foreach ($mapel as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= isset($_GET['mapel']) && $_GET['mapel'] == $m['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['nama_mapel']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3 mb-2">
                                    <label class="mr-2">Kelas:</label>
                                    <select name="kelas" class="form-control form-control-sm">
                                        <option value="">Semua</option>
                                        <?php foreach ($kelas as $k): ?>
                                            <option value="<?= $k['id'] ?>" <?= isset($_GET['kelas']) && $_GET['kelas'] == $k['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3 mb-2">
                                    <label class="mr-2">Jenis:</label>
                                    <select name="jenis" class="form-control form-control-sm">
                                        <option value="">Semua</option>
                                        <option value="pilihan_ganda" <?= isset($_GET['jenis']) && $_GET['jenis'] == 'pilihan_ganda' ? 'selected' : '' ?>>Pilihan Ganda</option>
                                        <option value="esai" <?= isset($_GET['jenis']) && $_GET['jenis'] == 'esai' ? 'selected' : '' ?>>Esai</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mr-2">Filter</button>
                                <a href="bank_soal.php" class="btn btn-secondary btn-sm">Reset</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Soal (<?= $total_rows ?> soal)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Kelas</th>
                                    <th>Jenis</th>
                                    <th>Pertanyaan</th>
                                    <th>Bobot</th>
                                    <th>Kesulitan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($soal as $index => $s): ?>
                                <tr>
                                    <td><?= ($page - 1) * $per_page + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($s['nama_mapel']) ?></td>
                                    <td><?= htmlspecialchars($s['nama_kelas']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $s['jenis_soal'] == 'pilihan_ganda' ? 'primary' : 'info' ?>">
                                            <?= $s['jenis_soal'] == 'pilihan_ganda' ? 'PG' : 'Esai' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($s['gambar']): ?>
                                            <img src="../uploads/soal/<?= $s['gambar'] ?>" alt="Gambar Soal" style="max-width: 100px; max-height: 100px;">
                                        <?php endif; ?>
                                        <div><?= substr(strip_tags($s['pertanyaan']), 0, 100) ?>...</div>
                                    </td>
                                    <td><?= $s['bobot_soal'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $s['tingkat_kesulitan'] == 'mudah' ? 'success' : 
                                            ($s['tingkat_kesulitan'] == 'sedang' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($s['tingkat_kesulitan']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" 
                                                data-toggle="modal" data-target="#detailModal<?= $s['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-toggle="modal" data-target="#editModal<?= $s['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-toggle="modal" data-target="#hapusModal<?= $s['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= isset($_GET['mapel']) ? '&mapel=' . $_GET['mapel'] : '' ?><?= isset($_GET['kelas']) ? '&kelas=' . $_GET['kelas'] : '' ?><?= isset($_GET['jenis']) ? '&jenis=' . $_GET['jenis'] : '' ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['mapel']) ? '&mapel=' . $_GET['mapel'] : '' ?><?= isset($_GET['kelas']) ? '&kelas=' . $_GET['kelas'] : '' ?><?= isset($_GET['jenis']) ? '&jenis=' . $_GET['jenis'] : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= isset($_GET['mapel']) ? '&mapel=' . $_GET['mapel'] : '' ?><?= isset($_GET['kelas']) ? '&kelas=' . $_GET['kelas'] : '' ?><?= isset($_GET['jenis']) ? '&jenis=' . $_GET['jenis'] : '' ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Soal -->
<div class="modal fade" id="tambahModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Soal Baru</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mata Pelajaran</label>
                                <select name="mata_pelajaran_id" class="form-control" required>
                                    <option value="">Pilih Mata Pelajaran</option>
                                    <?php foreach ($mapel as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
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
                                <label>Jenis Soal</label>
                                <select name="jenis_soal" class="form-control" required>
                                    <option value="pilihan_ganda">Pilihan Ganda</option>
                                    <option value="esai">Esai</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Bobot Soal</label>
                                <input type="number" name="bobot_soal" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Tingkat Kesulitan</label>
                                <select name="tingkat_kesulitan" class="form-control" required>
                                    <option value="mudah">Mudah</option>
                                    <option value="sedang" selected>Sedang</option>
                                    <option value="sulit">Sulit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Gambar (opsional)</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pertanyaan</label>
                        <textarea name="pertanyaan" class="form-control" rows="4" required></textarea>
                    </div>
                    <div id="pilihan-ganda-fields">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Pilihan A</label>
                                    <input type="text" name="pilihan_a" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Pilihan B</label>
                                    <input type="text" name="pilihan_b" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Pilihan C</label>
                                    <input type="text" name="pilihan_c" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Pilihan D</label>
                                    <input type="text" name="pilihan_d" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Pilihan E</label>
                                    <input type="text" name="pilihan_e" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jawaban Benar</label>
                                    <select name="jawaban_benar" class="form-control">
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="E">E</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_soal" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('select[name="jenis_soal"]').addEventListener('change', function() {
    const pilihanFields = document.getElementById('pilihan-ganda-fields');
    if (this.value === 'pilihan_ganda') {
        pilihanFields.style.display = 'block';
    } else {
        pilihanFields.style.display = 'none';
    }
});
</script>

        </main>
    </div>
</div>

<?php include 'includes/footer-modern.php'; ?>