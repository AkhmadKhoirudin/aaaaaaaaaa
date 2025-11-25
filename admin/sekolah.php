<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Cek login dan role
checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Proses form
if ($_POST) {
    if (isset($_POST['update_sekolah'])) {
        $nama_sekolah = $_POST['nama_sekolah'];
        $alamat = $_POST['alamat'];
        $npsn = $_POST['npsn'];
        $tahun_ajaran = $_POST['tahun_ajaran'];
        
        // Handle upload logo
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $uploadDir = '../uploads/sekolah/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo = 'logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logo);
            
            // Hapus logo lama jika ada
            $oldLogo = $pdo->query("SELECT logo FROM sekolah WHERE id = 1")->fetchColumn();
            if ($oldLogo && file_exists($uploadDir . $oldLogo)) {
                unlink($uploadDir . $oldLogo);
            }
        }
        
        $sql = "UPDATE sekolah SET 
                nama_sekolah = ?, 
                alamat = ?, 
                npsn = ?, 
                tahun_ajaran = ?";
        
        $params = [$nama_sekolah, $alamat, $npsn, $tahun_ajaran];
        
        if ($logo) {
            $sql .= ", logo = ?";
            $params[] = $logo;
        }
        
        $sql .= " WHERE id = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['success'] = 'Data sekolah berhasil diperbarui';
        header('Location: sekolah.php');
        exit;
    }
}

// Ambil data sekolah
$sekolah = $pdo->query("SELECT * FROM sekolah WHERE id = 1")->fetch();

$page_title = 'Data Sekolah';
include 'includes/header-modern.php';
?>

<!-- Page Header -->
<div class="content-header">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-school text-gradient me-2"></i>
                Data Sekolah
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Data Sekolah</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-custom">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="fas fa-info-circle text-gradient me-2"></i>
            Identitas Sekolah
        </h5>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="nama_sekolah" class="form-label">Nama Sekolah</label>
                        <input type="text" id="nama_sekolah" name="nama_sekolah" class="form-control" 
                               value="<?= htmlspecialchars($sekolah['nama_sekolah'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="npsn" class="form-label">NPSN</label>
                        <input type="text" id="npsn" name="npsn" class="form-control" 
                               value="<?= htmlspecialchars($sekolah['npsn'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                        <input type="text" id="tahun_ajaran" name="tahun_ajaran" class="form-control" 
                               value="<?= htmlspecialchars($sekolah['tahun_ajaran'] ?? '') ?>" 
                               placeholder="Contoh: 2024/2025">
                    </div>
                     <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat Sekolah</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3"><?= 
                            htmlspecialchars($sekolah['alamat'] ?? '') 
                        ?></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo Sekolah</label>
                        <div class="mb-2 text-center">
                            <?php if (!empty($sekolah['logo'])): ?>
                                <img src="../uploads/sekolah/<?= $sekolah['logo'] ?>" 
                                     alt="Logo" class="img-thumbnail" style="max-height: 150px;">
                            <?php else: ?>
                                <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; background-color: #f8f9fa;">
                                    <span class="text-muted">No Logo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                        <small class="form-text text-muted">Format: JPG, PNG. Max: 2MB</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent text-end">
            <button type="submit" name="update_sekolah" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer-modern.php'; ?>