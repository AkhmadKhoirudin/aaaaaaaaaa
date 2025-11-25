<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role
requireLogin();
requireRole(['admin', 'operator', 'guru', 'pengawas']);

$page_title = 'Dashboard';
include 'includes/header-modern.php';

// Statistik dashboard
$stats = [];

// Total peserta
$stmt = $pdo->query("SELECT COUNT(*) as total FROM peserta");
$stats['total_peserta'] = $stmt->fetch()['total'];

// Total ujian aktif
$stmt = $pdo->query("SELECT COUNT(*) as total FROM paket_soal WHERE status = 'aktif'");
$stats['total_ujian_aktif'] = $stmt->fetch()['total'];

// Total ujian berlangsung
$stmt = $pdo->query("SELECT COUNT(*) as total FROM sesi_ujian WHERE status = 'sedang_ujian'");
$stats['total_ujian_berlangsung'] = $stats['total_ujian_berlangsung'] = $stmt->fetch()['total'];

// Total ujian selesai hari ini
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sesi_ujian WHERE DATE(created_at) = ? AND status = 'selesai'");
$stmt->execute([$today]);
$stats['total_ujian_selesai_hari_ini'] = $stmt->fetch()['total'];

// Aktivitas terbaru
$stmt = $pdo->query("SELECT al.*, u.nama_lengkap, u.role 
                     FROM activity_logs al 
                     JOIN users u ON al.user_id = u.id 
                     ORDER BY al.created_at DESC 
                     LIMIT 10");
$recent_activities = $stmt->fetchAll();

// Status server
$server_status = [
    'database' => checkDatabaseConnection() ? 'Online' : 'Offline',
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
    'disk_free' => disk_free_space(".") / 1024 / 1024 / 1024 . ' GB'
];
?>

<!-- Page Header -->
<div class="content-header">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt text-gradient me-2"></i>
                Dashboard
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <span id="periodText">Hari Ini</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="setPeriod('today')">Hari Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setPeriod('week')">Minggu Ini</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setPeriod('month')">Bulan Ini</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-custom hover-lift">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-muted mb-1 fw-bold" style="font-size: 0.75rem;">
                            Total Peserta
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark">
                            <?php echo number_format($stats['total_peserta']); ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="fas fa-arrow-up me-1"></i>
                                <span id="pesertaGrowth">+12%</span>
                            </small>
                            <small class="text-muted ms-2">vs bulan lalu</small>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="bg-gradient-primary rounded-3 p-3 text-white">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-custom hover-lift">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-muted mb-1 fw-bold" style="font-size: 0.75rem;">
                            Ujian Aktif
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark">
                            <?php echo number_format($stats['total_ujian_aktif']); ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-info">
                                <i class="fas fa-circle me-1"></i>
                                <span id="activeExams">Aktif</span>
                            </small>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="bg-success rounded-3 p-3 text-white">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-custom hover-lift">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-muted mb-1 fw-bold" style="font-size: 0.75rem;">
                            Sedang Ujian
                        </div>
                        <div class="h3 mb-0 fw-bold text-warning">
                            <?php echo number_format($stats['total_ujian_berlangsung']); ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-warning">
                                <i class="fas fa-clock me-1"></i>
                                <span id="ongoingExams">Berlangsung</span>
                            </small>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="bg-warning rounded-3 p-3 text-white">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-custom hover-lift">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-muted mb-1 fw-bold" style="font-size: 0.75rem;">
                            Selesai Hari Ini
                        </div>
                        <div class="h3 mb-0 fw-bold text-info">
                            <?php echo number_format($stats['total_ujian_selesai_hari_ini']); ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-info">
                                <i class="fas fa-check-circle me-1"></i>
                                <span id="completedToday">Hari ini</span>
                            </small>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="bg-info rounded-3 p-3 text-white">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Analytics -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-custom">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line text-gradient me-2"></i>
                    Statistik Ujian
                </h5>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshChart()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="examChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-custom">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie text-gradient me-2"></i>
                    Distribusi Nilai
                </h5>
            </div>
            <div class="card-body">
                <canvas id="gradeChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Server Status -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-custom">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="fas fa-server text-gradient me-2"></i>
                    Status Server
                </h5>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshServerStatus()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="fw-bold text-muted">Database</td>
                            <td>
                                <span class="badge bg-<?php echo $server_status['database'] == 'Online' ? 'success' : 'danger'; ?> rounded-pill">
                                    <i class="fas fa-circle me-1"></i>
                                    <?php echo $server_status['database']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">PHP Version</td>
                            <td>
                                <span class="badge bg-secondary rounded-pill">
                                    <?php echo $server_status['php_version']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Server Software</td>
                            <td>
                                <small class="text-muted"><?php echo $server_status['server_software']; ?></small>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Memory Usage</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-info" role="progressbar"
                                         style="width: <?php echo min(75, rand(30, 70)); ?>%">
                                        <?php echo $server_status['memory_usage']; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Disk Free</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: <?php echo min(90, rand(60, 85)); ?>%">
                                        <?php echo round((float)$server_status['disk_free'], 2) . ' GB'; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-custom">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history text-gradient me-2"></i>
                    Aktivitas Terbaru
                </h5>
                <div class="card-tools">
                    <a href="logs/index.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_activities as $index => $activity): ?>
                        <div class="list-group-item list-group-item-action border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon bg-<?php echo $index % 2 == 0 ? 'primary' : 'success'; ?> bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-history text-<?php echo $index % 2 == 0 ? 'primary' : 'success'; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($activity['activity']); ?></h6>
                                        <p class="mb-1 small text-muted">
                                            <?php echo htmlspecialchars($activity['nama_lengkap']); ?>
                                            <span class="badge bg-<?php echo $activity['role'] == 'admin' ? 'danger' : ($activity['role'] == 'operator' ? 'warning' : 'info'); ?> rounded-pill">
                                                <?php echo ucfirst($activity['role']); ?>
                                            </span>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo formatDate($activity['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($recent_activities)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <p>Tidak ada aktivitas terbaru</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card border-0 shadow-custom">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="fas fa-bolt text-gradient me-2"></i>
            Aksi Cepat
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <a href="users.php?role=peserta" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                    <i class="fas fa-user-plus me-2"></i>
                    <span>Tambah Peserta</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="bank_soal.php" class="btn btn-success w-100 d-flex align-items-center justify-content-center">
                    <i class="fas fa-plus me-2"></i>
                    <span>Tambah Soal</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="ujian.php" class="btn btn-info w-100 d-flex align-items-center justify-content-center">
                    <i class="fas fa-edit me-2"></i>
                    <span>Buat Ujian</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="monitoring_ujian.php" class="btn btn-warning w-100 d-flex align-items-center justify-content-center">
                    <i class="fas fa-desktop me-2"></i>
                    <span>Monitoring Ujian</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Scripts -->
<script>
    // Dashboard functionality
    let examChart, gradeChart;
    
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        updateRealTimeData();
        setInterval(updateRealTimeData, 30000); // Update every 30 seconds
    });
    
    // Initialize charts
    function initCharts() {
        // Exam statistics chart
        const examCtx = document.getElementById('examChart');
        if (examCtx) {
            examChart = new Chart(examCtx, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Ujian Dimulai',
                        data: [12, 19, 15, 25, 22, 30, 28],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Ujian Selesai',
                        data: [10, 15, 12, 20, 18, 25, 22],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Grade distribution chart
        const gradeCtx = document.getElementById('gradeChart');
        if (gradeCtx) {
            gradeChart = new Chart(gradeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['A', 'B', 'C', 'D', 'E'],
                    datasets: [{
                        data: [30, 25, 20, 15, 10],
                        backgroundColor: [
                            '#10b981',
                            '#6366f1',
                            '#f59e0b',
                            '#ef4444',
                            '#64748b'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
    }
    
    // Update real-time data
    function updateRealTimeData() {
        // Simulate real-time updates
        const elements = {
            pesertaGrowth: document.getElementById('pesertaGrowth'),
            activeExams: document.getElementById('activeExams'),
            ongoingExams: document.getElementById('ongoingExams'),
            completedToday: document.getElementById('completedToday')
        };
        
        // Update with random variations
        if (elements.pesertaGrowth) {
            elements.pesertaGrowth.textContent = '+' + (Math.random() * 20 + 5).toFixed(0) + '%';
        }
        
        if (elements.activeExams) {
            elements.activeExams.textContent = 'Aktif';
        }
        
        if (elements.ongoingExams) {
            elements.ongoingExams.textContent = 'Berlangsung';
        }
        
        if (elements.completedToday) {
            elements.completedToday.textContent = 'Hari ini';
        }
    }
    
    // Refresh dashboard
    function refreshDashboard() {
        Utils.showLoading('Memperbarui dashboard...');
        
        setTimeout(() => {
            // Simulate data refresh
            updateRealTimeData();
            Utils.hideLoading();
            Utils.showSuccess('Dashboard berhasil diperbarui');
        }, 1000);
    }
    
    // Set period
    function setPeriod(period) {
        const periodText = document.getElementById('periodText');
        const periods = {
            'today': 'Hari Ini',
            'week': 'Minggu Ini',
            'month': 'Bulan Ini'
        };
        
        if (periodText && periods[period]) {
            periodText.textContent = periods[period];
            refreshDashboard();
        }
    }
    
    // Refresh chart
    function refreshChart() {
        if (examChart) {
            examChart.update();
        }
        Utils.showInfo('Chart diperbarui');
    }
    
    // Refresh server status
    function refreshServerStatus() {
        Utils.showLoading('Memperbarui status server...');
        
        setTimeout(() => {
            Utils.hideLoading();
            Utils.showSuccess('Status server diperbarui');
        }, 800);
    }
</script>

<!-- Add Chart.js for charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php include 'includes/footer-modern.php'; ?>