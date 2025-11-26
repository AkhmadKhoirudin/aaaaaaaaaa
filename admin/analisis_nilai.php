<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();
if (!hasRole(['admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Set page title
$page_title = 'Analisis Nilai';

include 'includes/header-modern.php';

// Ambil filter
$ujian_id = $_GET['ujian_id'] ?? 0;
$kelas_id = $_GET['kelas_id'] ?? 0;

// Ambil daftar ujian
$stmt = $pdo->query("SELECT id, nama FROM ujian ORDER BY nama");
$ujians = $stmt->fetchAll();

// Ambil daftar kelas
$stmt = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
$kelas = $stmt->fetchAll();

// Query untuk analisis nilai
$where = [];
$params = [];

if ($ujian_id > 0) {
    $where[] = "u.id = ?";
    $params[] = $ujian_id;
}

if ($kelas_id > 0) {
    $where[] = "k.id = ?";
    $params[] = $kelas_id;
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Ambil data nilai
$stmt = $pdo->prepare("
    SELECT
        u.nama_lengkap as peserta_nama,
        ps.nis,
        k.nama_kelas,
        uj.nama as ujian_nama,
        su.nilai,
        su.benar,
        su.salah,
        su.kosong,
        su.waktu_mulai,
        su.waktu_selesai,
        TIMESTAMPDIFF(MINUTE, su.waktu_mulai, su.waktu_selesai) as durasi_menit
    FROM sesi_ujian su
    JOIN peserta ps ON su.peserta_id = ps.id
    JOIN users u ON ps.user_id = u.id
    JOIN kelas k ON ps.kelas_id = k.id
    JOIN ujian uj ON su.ujian_id = uj.id
    $where_clause
    AND su.status = 'selesai'
    ORDER BY k.nama_kelas, u.nama_lengkap
");
$stmt->execute($params);
$nilai_data = $stmt->fetchAll();

// Hitung statistik
$total_peserta = count($nilai_data);
$nilai_array = array_column($nilai_data, 'nilai');
$nilai_tertinggi = $total_peserta > 0 ? max($nilai_array) : 0;
$nilai_terendah = $total_peserta > 0 ? min($nilai_array) : 0;
$rata_rata = $total_peserta > 0 ? round(array_sum($nilai_array) / $total_peserta, 2) : 0;

// Hitung distribusi nilai
$distribusi = [
    'A' => 0, // 80-100
    'B' => 0, // 70-79
    'C' => 0, // 60-69
    'D' => 0, // 50-59
    'E' => 0  // <50
];

foreach ($nilai_array as $nilai) {
    if ($nilai >= 80) $distribusi['A']++;
    elseif ($nilai >= 70) $distribusi['B']++;
    elseif ($nilai >= 60) $distribusi['C']++;
    elseif ($nilai >= 50) $distribusi['D']++;
    else $distribusi['E']++;
}

// Ambil analisis per soal
if ($ujian_id > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            bs.id as soal_id,
            bs.pertanyaan,
            bs.jawaban_benar,
            COUNT(jp.id) as total_jawaban,
            SUM(CASE WHEN jp.jawaban = bs.jawaban_benar THEN 1 ELSE 0 END) as benar,
            SUM(CASE WHEN jp.jawaban != bs.jawaban_benar AND jp.jawaban IS NOT NULL THEN 1 ELSE 0 END) as salah,
            SUM(CASE WHEN jp.jawaban IS NULL THEN 1 ELSE 0 END) as kosong
        FROM bank_soal bs
        JOIN ujian u ON bs.mata_pelajaran_id = u.paket_soal_id
        JOIN sesi_ujian su ON u.id = su.ujian_id
        LEFT JOIN jawaban_peserta jp ON bs.id = jp.soal_id AND jp.sesi_ujian_id = su.id
        WHERE u.id = ? AND su.status = 'selesai'
        GROUP BY bs.id
        ORDER BY bs.id
    ");
    $stmt->execute([$ujian_id]);
    $analisis_soal = $stmt->fetchAll();
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Analisis Nilai</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Analisis Nilai</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filter Data</h3>
                </div>
                <div class="card-body">
                    <form method="get">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ujian</label>
                                    <select name="ujian_id" class="form-control">
                                        <option value="0">-- Semua Ujian --</option>
                                        <?php foreach ($ujians as $ujian): ?>
                                            <option value="<?= $ujian['id'] ?>" <?= $ujian_id == $ujian['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ujian['nama']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kelas</label>
                                    <select name="kelas_id" class="form-control">
                                        <option value="0">-- Semua Kelas --</option>
                                        <?php foreach ($kelas as $k): ?>
                                            <option value="<?= $k['id'] ?>" <?= $kelas_id == $k['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="export_nilai.php?<?= http_build_query($_GET) ?>" class="btn btn-success">
                                            <i class="fas fa-download"></i> Export Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistik -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $total_peserta ?></h3>
                            <p>Total Peserta</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $nilai_tertinggi ?></h3>
                            <p>Nilai Tertinggi</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $nilai_terendah ?></h3>
                            <p>Nilai Terendah</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $rata_rata ?></h3>
                            <p>Rata-rata</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik Distribusi Nilai -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Distribusi Nilai</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="distribusiChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ringkasan Nilai</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <td><strong>Total Peserta</strong></td>
                                    <td><?= $total_peserta ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Rata-rata</strong></td>
                                    <td><?= $rata_rata ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Standar Deviasi</strong></td>
                                    <td>
                                        <?php
                                        if ($total_peserta > 1) {
                                            $variance = 0;
                                            foreach ($nilai_array as $nilai) {
                                                $variance += pow($nilai - $rata_rata, 2);
                                            }
                                            $std_dev = round(sqrt($variance / ($total_peserta - 1)), 2);
                                            echo $std_dev;
                                        } else {
                                            echo "0";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Median</strong></td>
                                    <td>
                                        <?php
                                        if ($total_peserta > 0) {
                                            sort($nilai_array);
                                            $middle = floor($total_peserta / 2);
                                            $median = $total_peserta % 2 
                                                ? $nilai_array[$middle] 
                                                : round(($nilai_array[$middle - 1] + $nilai_array[$middle]) / 2, 2);
                                            echo $median;
                                        } else {
                                            echo "0";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Nilai -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Nilai Peserta</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="nilaiTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Peserta</th>
                                    <th>NIS</th>
                                    <th>Kelas</th>
                                    <th>Ujian</th>
                                    <th>Nilai</th>
                                    <th>Benar</th>
                                    <th>Salah</th>
                                    <th>Kosong</th>
                                    <th>Durasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nilai_data as $index => $data): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($data['peserta_nama']) ?></td>
                                        <td><?= htmlspecialchars($data['nis']) ?></td>
                                        <td><?= htmlspecialchars($data['nama_kelas']) ?></td>
                                        <td><?= htmlspecialchars($data['ujian_nama']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $data['nilai'] >= 70 ? 'success' : 'danger' ?>">
                                                <?= $data['nilai'] ?>
                                            </span>
                                        </td>
                                        <td><?= $data['benar'] ?></td>
                                        <td><?= $data['salah'] ?></td>
                                        <td><?= $data['kosong'] ?></td>
                                        <td><?= $data['durasi_menit'] ?> menit</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Analisis Per Soal -->
            <?php if (isset($analisis_soal) && !empty($analisis_soal)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Analisis Per Soal</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Pertanyaan</th>
                                    <th>Jawaban Benar</th>
                                    <th>Total</th>
                                    <th>Benar</th>
                                    <th>Salah</th>
                                    <th>Kosong</th>
                                    <th>% Benar</th>
                                    <th>Tingkat Kesulitan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analisis_soal as $index => $soal): ?>
                                    <?php
                                    $persen_benar = $soal['total_jawaban'] > 0 
                                        ? round(($soal['benar'] / $soal['total_jawaban']) * 100, 2) 
                                        : 0;
                                    
                                    if ($persen_benar >= 70) {
                                        $tingkat = 'Mudah';
                                        $badge = 'success';
                                    } elseif ($persen_benar >= 40) {
                                        $tingkat = 'Sedang';
                                        $badge = 'warning';
                                    } else {
                                        $tingkat = 'Sulit';
                                        $badge = 'danger';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= substr(htmlspecialchars($soal['pertanyaan']), 0, 100) ?>...</td>
                                        <td><?= $soal['jawaban_benar'] ?></td>
                                        <td><?= $soal['total_jawaban'] ?></td>
                                        <td><?= $soal['benar'] ?></td>
                                        <td><?= $soal['salah'] ?></td>
                                        <td><?= $soal['kosong'] ?></td>
                                        <td><?= $persen_benar ?>%</td>
                                        <td>
                                            <span class="badge badge-<?= $badge ?>"><?= $tingkat ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart untuk distribusi nilai
const ctx = document.getElementById('distribusiChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['A (80-100)', 'B (70-79)', 'C (60-69)', 'D (50-59)', 'E (<50)'],
        datasets: [{
            label: 'Jumlah Peserta',
            data: [<?= $distribusi['A'] ?>, <?= $distribusi['B'] ?>, <?= $distribusi['C'] ?>, <?= $distribusi['D'] ?>, <?= $distribusi['E'] ?>],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(23, 162, 184, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(255, 153, 0, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ],
            borderColor: [
                'rgba(40, 167, 69, 1)',
                'rgba(23, 162, 184, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(255, 153, 0, 1)',
                'rgba(220, 53, 69, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// DataTables
$(document).ready(function() {
    $('#nilaiTable').DataTable({
        responsive: true,
        order: [[5, 'desc']]
    });
});
</script>

<?php include 'includes/footer-modern.php'; ?>