<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role peserta
requireLogin();
requireRole(['peserta']);

// Ambil data peserta
$stmt = $pdo->prepare("SELECT p.*, k.nama_kelas FROM peserta p JOIN kelas k ON p.kelas_id = k.id WHERE p.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$peserta = $stmt->fetch();

// Ambil ujian yang tersedia untuk peserta ini
$stmt = $pdo->prepare("SELECT ps.*, mp.nama_mapel, j.tanggal_ujian, j.waktu_mulai, j.waktu_selesai
                      FROM paket_soal ps
                      JOIN mata_pelajaran mp ON ps.mata_pelajaran_id = mp.id
                      JOIN jadwal_ujian j ON ps.id = j.paket_soal_id
                      WHERE ps.kelas_id = ? AND ps.status = 'aktif'
                      AND j.tanggal_ujian = CURDATE()
                      AND CURTIME() BETWEEN j.waktu_mulai AND j.waktu_selesai
                      ORDER BY j.waktu_mulai ASC");
$stmt->execute([$peserta['kelas_id']]);
$ujian_tersedia = $stmt->fetchAll();

// Ambil riwayat ujian peserta
$stmt = $pdo->prepare("SELECT su.*, ps.nama_paket, mp.nama_mapel, ps.tampilkan_nilai
                      FROM sesi_ujian su
                      JOIN paket_soal ps ON su.paket_soal_id = ps.id
                      JOIN mata_pelajaran mp ON ps.mata_pelajaran_id = mp.id
                      WHERE us.peserta_id = ?
                      ORDER BY us.created_at DESC
                      LIMIT 10");
$stmt->execute([$peserta['id']]);
$riwayat_ujian = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peserta - CBT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/peserta.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> CBT Peserta
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ujian.php">Ujian</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="hasil.php">Hasil</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4>Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?>!</h4>
                                <p class="text-muted mb-1">NIS: <?php echo $peserta['nis']; ?></p>
                                <p class="text-muted mb-0">Kelas: <?php echo $peserta['nama_kelas']; ?></p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <img src="../assets/img/student-avatar.png" alt="Avatar" class="rounded-circle" width="80" height="80">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ujian Tersedia -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clock"></i> Ujian Tersedia Hari Ini
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($ujian_tersedia) > 0): ?>
                            <div class="row">
                                <?php foreach ($ujian_tersedia as $ujian): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-left-success">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo $ujian['nama_mapel']; ?></h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-book"></i> <?php echo $ujian['nama_paket']; ?><br>
                                                        <i class="fas fa-clock"></i> <?php echo $ujian['durasi_menit']; ?> menit<br>
                                                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($ujian['tanggal_ujian'])); ?><br>
                                                        <i class="fas fa-clock"></i> <?php echo substr($ujian['waktu_mulai'], 0, 5); ?> - <?php echo substr($ujian['waktu_selesai'], 0, 5); ?>
                                                    </small>
                                                </p>
                                                
                                                <?php
                                                // Cek apakah peserta sudah mengikuti ujian ini
                                                $stmt = $pdo->prepare("SELECT * FROM sesi_ujian WHERE peserta_id = ? AND paket_soal_id = ?");
                                                $stmt->execute([$peserta['id'], $ujian['id']]);
                                                $sudah_ujian = $stmt->fetch();
                                                
                                                if ($sudah_ujian):
                                                    if ($sudah_ujian['status'] == 'selesai'):
                                                ?>
                                                    <span class="badge bg-success">Sudah Selesai</span>
                                                <?php else: ?>
                                                    <a href="ujian_lanjut.php?token=<?php echo $sudah_ujian['token_ujian']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-play"></i> Lanjutkan
                                                    </a>
                                                <?php endif; else: ?>
                                                    <a href="mulai_ujian.php?paket=<?php echo $ujian['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-play"></i> Mulai Ujian
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada ujian yang tersedia hari ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Ujian -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Riwayat Ujian
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($riwayat_ujian) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mata Pelajaran</th>
                                            <th>Paket Soal</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Nilai</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($riwayat_ujian as $ujian): ?>
                                            <tr>
                                                <td><?php echo $ujian['nama_mapel']; ?></td>
                                                <td><?php echo $ujian['nama_paket']; ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($ujian['created_at'])); ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = '';
                                                    switch ($ujian['status']) {
                                                        case 'selesai':
                                                            $badge_class = 'success';
                                                            break;
                                                        case 'sedang_ujian':
                                                            $badge_class = 'warning';
                                                            break;
                                                        case 'force_submit':
                                                            $badge_class = 'danger';
                                                            break;
                                                        default:
                                                            $badge_class = 'secondary';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                                        <?php echo ucfirst($ujian['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($ujian['status'] == 'selesai'): ?>
                                                        <?php if ($ujian['tampilkan_nilai']): ?>
                                                            <span class="badge bg-primary"><?php echo $ujian['nilai']; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Ditunda</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($ujian['status'] == 'sedang_ujian'): ?>
                                                        <a href="ujian_lanjut.php?token=<?php echo $ujian['token_ujian']; ?>" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-play"></i> Lanjutkan
                                                        </a>
                                                    <?php elseif ($ujian['status'] == 'selesai' && $ujian['tampilkan_nilai']): ?>
                                                        <a href="hasil_detail.php?token=<?php echo $ujian['token_ujian']; ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> Lihat
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada riwayat ujian.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh setiap 30 detik untuk cek ujian baru
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>