<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil informasi user
$user_nama = $_SESSION['nama_lengkap'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'admin';
$user_avatar = 'assets/img/user.png';

// Definisikan menu berdasarkan role
$menu_items = [
    'admin' => [
        ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => 'dashboard.php'],
        ['title' => 'Manajemen User', 'icon' => 'fas fa-users', 'url' => 'users.php'],
        ['title' => 'Sekolah', 'icon' => 'fas fa-school', 'url' => 'sekolah.php'],
        ['title' => 'Kelas', 'icon' => 'fas fa-door-open', 'url' => 'kelas.php'],
        ['title' => 'Mata Pelajaran', 'icon' => 'fas fa-book', 'url' => 'mapel.php'],
        ['title' => 'Ruang Ujian', 'icon' => 'fas fa-building', 'url' => 'ruang.php'],
        ['title' => 'Bank Soal', 'icon' => 'fas fa-question-circle', 'url' => 'bank_soal.php'],
        ['title' => 'Paket Soal', 'icon' => 'fas fa-folder-open', 'url' => 'paket_soal.php'],
        ['title' => 'Ujian', 'icon' => 'fas fa-edit', 'url' => 'ujian.php'],
        ['title' => 'Peserta', 'icon' => 'fas fa-user-graduate', 'url' => 'peserta.php'],
        ['title' => 'Monitoring Ujian', 'icon' => 'fas fa-desktop', 'url' => 'monitoring_ujian.php'],
        ['title' => 'Analisis Nilai', 'icon' => 'fas fa-chart-bar', 'url' => 'analisis_nilai.php'],
        ['title' => 'Import Soal', 'icon' => 'fas fa-upload', 'url' => 'import_soal.php'],
        ['title' => 'Export Nilai', 'icon' => 'fas fa-download', 'url' => 'export_nilai.php'],
        ['title' => 'Cetak Kartu', 'icon' => 'fas fa-id-card', 'url' => 'cetak_kartu.php'],
        ['title' => 'Cetak Daftar Hadir', 'icon' => 'fas fa-list', 'url' => 'cetak_daftar_hadir.php'],
        ['title' => 'Cetak Berita Acara', 'icon' => 'fas fa-file-alt', 'url' => 'cetak_berita_acara.php'],
        ['title' => 'Log Pelanggaran', 'icon' => 'fas fa-exclamation-triangle', 'url' => 'log_violation.php'],
    ],
    'operator' => [
        ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => 'dashboard.php'],
        ['title' => 'Manajemen User', 'icon' => 'fas fa-users', 'url' => 'users.php'],
        ['title' => 'Sekolah', 'icon' => 'fas fa-school', 'url' => 'sekolah.php'],
        ['title' => 'Kelas', 'icon' => 'fas fa-door-open', 'url' => 'kelas.php'],
        ['title' => 'Mata Pelajaran', 'icon' => 'fas fa-book', 'url' => 'mapel.php'],
        ['title' => 'Ruang Ujian', 'icon' => 'fas fa-building', 'url' => 'ruang.php'],
        ['title' => 'Bank Soal', 'icon' => 'fas fa-question-circle', 'url' => 'bank_soal.php'],
        ['title' => 'Paket Soal', 'icon' => 'fas fa-folder-open', 'url' => 'paket_soal.php'],
        ['title' => 'Ujian', 'icon' => 'fas fa-edit', 'url' => 'ujian.php'],
        ['title' => 'Peserta', 'icon' => 'fas fa-user-graduate', 'url' => 'peserta.php'],
        ['title' => 'Monitoring Ujian', 'icon' => 'fas fa-desktop', 'url' => 'monitoring_ujian.php'],
        ['title' => 'Analisis Nilai', 'icon' => 'fas fa-chart-bar', 'url' => 'analisis_nilai.php'],
        ['title' => 'Import Soal', 'icon' => 'fas fa-upload', 'url' => 'import_soal.php'],
        ['title' => 'Export Nilai', 'icon' => 'fas fa-download', 'url' => 'export_nilai.php'],
        ['title' => 'Cetak Kartu', 'icon' => 'fas fa-id-card', 'url' => 'cetak_kartu.php'],
        ['title' => 'Cetak Daftar Hadir', 'icon' => 'fas fa-list', 'url' => 'cetak_daftar_hadir.php'],
        ['title' => 'Cetak Berita Acara', 'icon' => 'fas fa-file-alt', 'url' => 'cetak_berita_acara.php'],
    ],
    'guru' => [
        ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => 'dashboard.php'],
        ['title' => 'Bank Soal', 'icon' => 'fas fa-question-circle', 'url' => 'bank_soal.php'],
        ['title' => 'Paket Soal', 'icon' => 'fas fa-folder-open', 'url' => 'paket_soal.php'],
        ['title' => 'Ujian', 'icon' => 'fas fa-edit', 'url' => 'ujian.php'],
        ['title' => 'Monitoring Ujian', 'icon' => 'fas fa-desktop', 'url' => 'monitoring_ujian.php'],
        ['title' => 'Analisis Nilai', 'icon' => 'fas fa-chart-bar', 'url' => 'analisis_nilai.php'],
        ['title' => 'Import Soal', 'icon' => 'fas fa-upload', 'url' => 'import_soal.php'],
        ['title' => 'Export Nilai', 'icon' => 'fas fa-download', 'url' => 'export_nilai.php'],
    ],
    'pengawas' => [
        ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => 'dashboard.php'],
        ['title' => 'Monitoring Ujian', 'icon' => 'fas fa-desktop', 'url' => 'monitoring_ujian.php'],
        ['title' => 'Cetak Kartu', 'icon' => 'fas fa-id-card', 'url' => 'cetak_kartu.php'],
        ['title' => 'Cetak Daftar Hadir', 'icon' => 'fas fa-list', 'url' => 'cetak_daftar_hadir.php'],
        ['title' => 'Cetak Berita Acara', 'icon' => 'fas fa-file-alt', 'url' => 'cetak_berita_acara.php'],
    ]
];

// Ambil menu untuk role yang sesuai
$current_menu = $menu_items[$user_role] ?? $menu_items['admin'];

// Dapatkan nama file saat ini
$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi untuk mengecek apakah menu aktif
function isMenuActive($url, $current_page) {
    return $current_page === $url ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>CBT Online</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS untuk Navigation -->
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --header-height: 60px;
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .sidebar-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .sidebar.collapsed .sidebar-title {
            display: none;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu-item {
            margin: 2px 0;
        }
        
        .sidebar-menu-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 10px;
            position: relative;
        }
        
        .sidebar-menu-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .sidebar-menu-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-menu-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: white;
            border-radius: 0 2px 2px 0;
        }
        
        .sidebar-menu-icon {
            width: 20px;
            text-align: center;
            margin-right: 15px;
            font-size: 16px;
        }
        
        .sidebar.collapsed .sidebar-menu-text {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-menu-link {
            justify-content: center;
            padding: 12px;
            margin-right: 0;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            margin: 5px auto;
        }
        
        .sidebar.collapsed .sidebar-menu-icon {
            margin-right: 0;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        /* Header */
        .top-header {
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--dark-color);
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }
        
        .breadcrumb {
            margin: 0;
            background: none;
            padding: 0;
            margin-left: 15px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 25px;
            background: var(--light-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-dropdown:hover {
            background: #e2e8f0;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark-color);
            margin: 0;
        }
        
        .user-role {
            font-size: 12px;
            color: #6b7280;
            text-transform: capitalize;
        }
        
        /* Content Area */
        .content-area {
            padding: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
            
            .header-right .user-info {
                display: none;
            }
        }
        
        /* Utility Classes */
        .text-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .shadow-custom {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, var(--info-color), #2563eb);
        }
        
        .bg-gradient-danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h5 class="sidebar-title">CBT Online</h5>
        </div>
        
        <ul class="sidebar-menu">
            <?php foreach ($current_menu as $item): ?>
                <li class="sidebar-menu-item">
                    <a href="<?php echo $item['url']; ?>" 
                       class="sidebar-menu-link <?php echo isMenuActive($item['url'], $current_page); ?>"
                       title="<?php echo $item['title']; ?>">
                        <i class="sidebar-menu-icon <?php echo $item['icon']; ?>"></i>
                        <span class="sidebar-menu-text"><?php echo $item['title']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                        <?php if (isset($page_title)): ?>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            
            <div class="header-right">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger rounded-pill" id="notificationCount">0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <li><h6 class="dropdown-header">Notifikasi</h6></li>
                        <li><a class="dropdown-item" href="#">Tidak ada notifikasi baru</a></li>
                    </ul>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <div class="user-dropdown" data-bs-toggle="dropdown">
                        <img src="<?php echo $user_avatar; ?>" alt="User" class="user-avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user_nama); ?></div>
                            <div class="user-role"><?php echo htmlspecialchars($user_role); ?></div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="users.php?action=profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="users.php?action=settings"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <div class="content-area">