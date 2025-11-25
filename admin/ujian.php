<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data ujian
$ujian = $pdo->query("SELECT u.*, m.nama_mapel, k.nama_kelas 
                     FROM ujian u
                     JOIN mata_pelajaran mp ON u.mata_pelajaran_id = mp.id
                     JOIN kelas k ON u.kelas_id = k.id
                     ORDER BY u.tanggal_ujian DESC")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manajemen Ujian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Ujian</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Ujian</h3>
                    <a href="ujian_tambah.php" class="btn btn-primary float-right">
                        <i class="fas fa-plus"></i> Tambah Ujian
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Ujian</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Kelas</th>
                                    <th>Tanggal Ujian</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ujian as $index => $u): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($u['nama_ujian']) ?></td>
                                    <td><?= htmlspecialchars($u['nama_mapel']) ?></td>
                                    <td><?= htmlspecialchars($u['nama_kelas']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($u['tanggal_ujian'])) ?></td>
                                    <td><?= $u['durasi'] ?> menit</td>
                                    <td>
                                        <span class="badge badge-<?= $u['status'] == 'aktif' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($u['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="ujian_edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="cetak_daftar_hadir.php?ujian_id=<?= $u['id'] ?>&kelas_id=<?= $u['kelas_id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-print"></i> Daftar Hadir
                                        </a>
                                        <a href="cetak_berita_acara.php?ujian_id=<?= $u['id'] ?>" class="btn btn-sm btn-success" target="_blank">
                                            <i class="fas fa-file-alt"></i> Berita Acara
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>