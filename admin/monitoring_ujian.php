<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';

// Ambil data ujian yang sedang berlangsung
$stmt = $pdo->query("
    SELECT 
        u.id,
        u.nama as ujian_nama,
        p.nama as paket_nama,
        m.nama_mapel,
        k.nama_kelas,
        COUNT(DISTINCT pu.peserta_id) as total_peserta,
        COUNT(CASE WHEN pu.status = 'sedang_ujian' THEN 1 END) as sedang_ujian,
        COUNT(CASE WHEN pu.status = 'selesai' THEN 1 END) as sudah_selesai,
        COUNT(CASE WHEN pu.status = 'belum_mulai' THEN 1 END) as belum_mulai
    FROM ujian u
    JOIN paket_soal p ON u.paket_soal_id = p.id
    JOIN mata_pelajaran m ON p.mata_pelajaran_id = m.id
    LEFT JOIN kelas k ON p.kelas_id = k.id
    LEFT JOIN peserta_ujian pu ON u.id = pu.ujian_id
    WHERE u.status = 'aktif' 
    AND u.tanggal_mulai <= NOW() 
    AND u.tanggal_selesai >= NOW()
    GROUP BY u.id
    ORDER BY u.tanggal_mulai DESC
");
$ujian_aktif = $stmt->fetchAll();

// Ambil detail peserta yang sedang ujian
$stmt = $pdo->query("
    SELECT 
        su.id as sesi_id,
        ps.nama as peserta_nama,
        ps.nis,
        k.nama_kelas,
        u.nama as ujian_nama,
        su.waktu_mulai,
        TIMESTAMPDIFF(MINUTE, su.waktu_mulai, NOW()) as menit_berjalan,
        su.ip_address,
        su.tab_switch_count,
        su.status
    FROM sesi_ujian su
    JOIN peserta ps ON su.peserta_id = ps.id
    JOIN kelas k ON ps.kelas_id = k.id
    JOIN ujian u ON su.ujian_id = u.id
    WHERE su.status = 'sedang_ujian'
    ORDER BY su.waktu_mulai ASC
");
$peserta_ujian = $stmt->fetchAll();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Monitoring Ujian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Monitoring Ujian</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Statistik Ujian -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= count($ujian_aktif) ?></h3>
                            <p>Ujian Aktif</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= count($peserta_ujian) ?></h3>
                            <p>Sedang Ujian</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="totalPelanggaran">0</h3>
                            <p>Pelanggaran</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="offlineCount">0</h3>
                            <p>Offline</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-wifi-slash"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Ujian Aktif -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ujian yang Sedang Berlangsung</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Ujian</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Kelas</th>
                                            <th>Total Peserta</th>
                                            <th>Belum Mulai</th>
                                            <th>Sedang Ujian</th>
                                            <th>Selesai</th>
                                            <th>Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ujian_aktif as $ujian): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ujian['ujian_nama']) ?></td>
                                                <td><?= htmlspecialchars($ujian['nama_mapel']) ?></td>
                                                <td><?= htmlspecialchars($ujian['nama_kelas'] ?? 'Semua Kelas') ?></td>
                                                <td><?= $ujian['total_peserta'] ?></td>
                                                <td>
                                                    <span class="badge badge-secondary"><?= $ujian['belum_mulai'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-warning"><?= $ujian['sedang_ujian'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success"><?= $ujian['sudah_selesai'] ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $progress = $ujian['total_peserta'] > 0 
                                                        ? round(($ujian['sudah_selesai'] / $ujian['total_peserta']) * 100) 
                                                        : 0;
                                                    ?>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-success" style="width: <?= $progress ?>%">
                                                            <?= $progress ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Peserta yang Sedang Ujian -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Peserta yang Sedang Ujian</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" onclick="refreshData()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="pesertaTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Peserta</th>
                                            <th>NIS</th>
                                            <th>Kelas</th>
                                            <th>Ujian</th>
                                            <th>Waktu Mulai</th>
                                            <th>Durasi</th>
                                            <th>IP Address</th>
                                            <th>Pelanggaran</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($peserta_ujian as $index => $peserta): ?>
                                            <tr data-sesi-id="<?= $peserta['sesi_id'] ?>">
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($peserta['peserta_nama']) ?></td>
                                                <td><?= htmlspecialchars($peserta['nis']) ?></td>
                                                <td><?= htmlspecialchars($peserta['nama_kelas']) ?></td>
                                                <td><?= htmlspecialchars($peserta['ujian_nama']) ?></td>
                                                <td><?= date('H:i:s', strtotime($peserta['waktu_mulai'])) ?></td>
                                                <td><?= $peserta['menit_berjalan'] ?> menit</td>
                                                <td><?= $peserta['ip_address'] ?></td>
                                                <td>
                                                    <span class="badge badge-danger"><?= $peserta['tab_switch_count'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success status-online">Online</span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="forceSubmit(<?= $peserta['sesi_id'] ?>)">
                                                        <i class="fas fa-stop"></i> Force Submit
                                                    </button>
                                                </td>
                                            </tr>
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

<!-- Modal Force Submit -->
<div class="modal fade" id="forceSubmitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Force Submit</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghentikan ujian peserta ini?</p>
                <input type="hidden" id="forceSubmitSesiId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="confirmForceSubmit()">Ya, Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
let refreshInterval;

function refreshData() {
    location.reload();
}

function forceSubmit(sesiId) {
    $('#forceSubmitSesiId').val(sesiId);
    $('#forceSubmitModal').modal('show');
}

function confirmForceSubmit() {
    const sesiId = $('#forceSubmitSesiId').val();
    
    $.post('force_submit.php', {sesi_id: sesiId}, function(response) {
        if (response.success) {
            alert('Ujian berhasil dihentikan');
            location.reload();
        } else {
            alert('Gagal menghentikan ujian');
        }
    }, 'json');
}

// Auto refresh every 30 seconds
refreshInterval = setInterval(function() {
    // Check connection status
    $('.status-online').each(function() {
        const row = $(this).closest('tr');
        const sesiId = row.data('sesi-id');
        
        // Simulate connection check
        $.get('check_connection.php', {sesi_id: sesiId}, function(response) {
            if (response.online) {
                $(this).removeClass('badge-danger').addClass('badge-success').text('Online');
            } else {
                $(this).removeClass('badge-success').addClass('badge-danger').text('Offline');
            }
        }, 'json');
    });
}, 30000);

// Stop auto refresh when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(refreshInterval);
    } else {
        refreshInterval = setInterval(refreshData, 30000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>