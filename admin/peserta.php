<?php
// Pastikan session dimulai sebelum semua operasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();
if (!hasRole(['admin', 'operator'])) {
    header('Location: dashboard.php');
    exit;
}

// Proses upload CSV dengan peningkatan validasi dan feedback
if (isset($_POST['upload_csv'])) {
    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
        $file = $_FILES['file_csv']['tmp_name'];
        $file_name = $_FILES['file_csv']['name'];
        $file_size = $_FILES['file_csv']['size'];
        $file_type = $_FILES['file_csv']['type'];
        
        // Validasi file
        if ($file_size > 5242880) { // 5MB max
            $_SESSION['error'] = "File terlalu besar. Maksimal 5MB";
            header('Location: peserta.php');
            exit;
        }
        
        // Validasi ekstensi file dan konten
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        if (strtolower($file_extension) !== 'csv') {
            $_SESSION['error'] = "Format file harus CSV";
            header('Location: peserta.php');
            exit;
        }
        
        // Validasi konten file (cek apakah bisa dibaca sebagai CSV)
        $handle = fopen($file, 'r');
        if ($handle === false) {
            $_SESSION['error'] = "File tidak dapat dibaca";
            header('Location: peserta.php');
            exit;
        }
        
        // Cek apakah file mengandung data CSV yang valid
        $first_line = fgets($handle);
        if (empty($first_line) || strpos($first_line, ',') === false) {
            fclose($handle);
            $_SESSION['error'] = "File tidak mengandung data CSV yang valid";
            header('Location: peserta.php');
            exit;
        }
        rewind($handle);
        
       // Deteksi encoding dan konversi ke UTF-8
       $content = file_get_contents($file);
       $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
       if ($encoding && $encoding !== 'UTF-8') {
           $content = mb_convert_encoding($content, 'UTF-8', $encoding);
           file_put_contents($file, $content);
       }
       
       // Deteksi delimiter (koma atau titik koma)
       $first_line = strtok($content, "\n");
       $delimiter = (strpos($first_line, ';') !== false && strpos($first_line, ',') === false) ? ';' : ',';
       
       $handle = fopen($file, 'r');
       
       if ($handle !== false) {
           $success_count = 0;
           $error_count = 0;
           $errors = [];
           $row_num = 0;
           $processed_rows = [];
           $duplicate_check = [];
           
           // Baca header
           $header = fgetcsv($handle, 0, $delimiter);
           if (!$header || count($header) < 7) {
               $_SESSION['error'] = "Format CSV tidak valid. Pastikan memiliki 7 kolom. Header ditemukan: " . implode(', ', $header ?: []);
               fclose($handle);
               header('Location: peserta.php');
               exit;
           }
           
           // Bersihkan header dari BOM jika ada
           $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
           
           // Baca baris per baris
           while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
               $row_num++;
               
               // Skip baris kosong atau header yang terduplikasi
               if (empty($data[1]) || trim($data[1]) === 'Nama') continue;
               
               // Bersihkan data dari karakter aneh
               $data = array_map(function($item) {
                   return trim(str_replace(["\r", "\n", "\t"], ' ', $item));
               }, $data);
               
               // Pastikan jumlah kolom cukup
               if (count($data) < 7) {
                   $errors[] = "Baris $row_num: Jumlah kolom tidak sesuai (memiliki " . count($data) . " kolom, harus 7)";
                   $error_count++;
                   continue;
               }
               
               $no = $data[0];
               $nama = $data[1];
               $nama_kelas = $data[2];
               $jenis_kelamin = strtoupper($data[3]);
               $username = $data[4];
               $password = $data[5];
               $status_aksi = strtolower($data[6]);
               
               // Validasi data dengan pesan error yang lebih jelas
               if (empty($nama) || empty($nama_kelas) || empty($jenis_kelamin) || empty($username) || empty($password)) {
                   $missing = [];
                   if (empty($nama)) $missing[] = "Nama";
                   if (empty($nama_kelas)) $missing[] = "Kelas";
                   if (empty($jenis_kelamin)) $missing[] = "Jenis Kelamin";
                   if (empty($username)) $missing[] = "Username";
                   if (empty($password)) $missing[] = "Password";
                   $errors[] = "Baris $row_num: Data tidak lengkap (" . implode(', ', $missing) . " kosong)";
                   $error_count++;
                   continue;
               }
               
               // Validasi nama (minimal 3 karakter)
               if (strlen($nama) < 3) {
                   $errors[] = "Baris $row_num: Nama terlalu pendek (minimal 3 karakter)";
                   $error_count++;
                   continue;
               }
               
               // Validasi jenis kelamin (lebih fleksibel)
               if (!in_array($jenis_kelamin, ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN', 'LAKI LAKI'])) {
                   $errors[] = "Baris $row_num: Jenis kelamin harus L/P (ditemukan: '$jenis_kelamin')";
                   $error_count++;
                   continue;
               }
               $jenis_kelamin = in_array($jenis_kelamin, ['L', 'LAKI-LAKI', 'LAKI LAKI']) ? 'L' : 'P';
               
               // Validasi username (alphanumeric dan underscore)
               if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                   $errors[] = "Baris $row_num: Username hanya boleh huruf, angka, dan underscore (ditemukan: '$username')";
                   $error_count++;
                   continue;
               }
               
               // Validasi password (minimal 4 karakter)
               if (strlen($password) < 4) {
                   $errors[] = "Baris $row_num: Password terlalu pendek (minimal 4 karakter)";
                   $error_count++;
                   continue;
               }
               
               // Validasi status aksi (lebih fleksibel)
               if (!in_array($status_aksi, ['aktif', 'nonaktif', '1', '0', 'true', 'false'])) {
                   $errors[] = "Baris $row_num: Status aksi harus Aktif/Nonaktif (ditemukan: '$status_aksi')";
                   $error_count++;
                   continue;
               }
               $is_active = in_array($status_aksi, ['aktif', '1', 'true']) ? 1 : 0;
               
               // Cek duplikasi username dalam file
               if (isset($duplicate_check[$username])) {
                   $errors[] = "Baris $row_num: Username '$username' duplikat dengan baris " . $duplicate_check[$username];
                   $error_count++;
                   continue;
               }
               $duplicate_check[$username] = $row_num;
               
               // Cari ID kelas berdasarkan nama kelas (case-insensitive)
               $stmt = $pdo->prepare("SELECT id FROM kelas WHERE LOWER(nama_kelas) = LOWER(?)");
               $stmt->execute([$nama_kelas]);
               $kelas = $stmt->fetch();
               
               if (!$kelas) {
                   $errors[] = "Baris $row_num: Kelas '$nama_kelas' tidak ditemukan";
                   $error_count++;
                   continue;
               }
               
               $kelas_id = $kelas['id'];
               
               // Simpan data untuk batch processing
               $processed_rows[] = [
                   'nama' => $nama,
                   'nama_kelas' => $nama_kelas,
                   'kelas_id' => $kelas_id,
                   'jenis_kelamin' => $jenis_kelamin,
                   'username' => $username,
                   'password' => $password,
                   'is_active' => $is_active,
                   'row_num' => $row_num
               ];
           }
           
           fclose($handle);
            
            // Proses data yang valid
            if (!empty($processed_rows)) {
                $pdo->beginTransaction();
                
                try {
                    foreach ($processed_rows as $row) {
                        // Cek apakah username sudah ada
                        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                        $check->execute([$row['username']]);
                        if ($check->fetchColumn() > 0) {
                            $errors[] = "Baris " . $row['row_num'] . ": Username '" . $row['username'] . "' sudah ada";
                            $error_count++;
                            continue;
                        }
                        
                        // Insert ke tabel users
                        $hashed_password = password_hash($row['password'], PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, role, is_active) VALUES (?, ?, ?, '', 'peserta', ?)");
                        $stmt->execute([$row['username'], $hashed_password, $row['nama'], $row['is_active']]);
                        $user_id = $pdo->lastInsertId();
                        
                        // Insert ke tabel peserta
                        $nis = $row['username']; // Gunakan username sebagai NIS
                        $stmt = $pdo->prepare("INSERT INTO peserta (user_id, nis, nisn, kelas_id, jenis_kelamin) VALUES (?, ?, '', ?, ?)");
                        $stmt->execute([$user_id, $nis, $row['kelas_id'], $row['jenis_kelamin']]);
                        
                        $success_count++;
                    }
                    
                    $pdo->commit();
                    
                    // Buat laporan detail
                    $report = "Upload selesai. Berhasil: $success_count, Gagal: $error_count";
                    if ($success_count > 0) {
                        $report .= "<br><small class='text-success'>✓ $success_count peserta berhasil ditambahkan</small>";
                    }
                    if ($error_count > 0) {
                        $report .= "<br><small class='text-danger'>✗ $error_count peserta gagal ditambahkan</small>";
                    }
                    
                    $_SESSION['success'] = $report;
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
                }
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', array_slice($errors, 0, 15)) .
                                   (count($errors) > 15 ? '<br><em>... dan ' . (count($errors) - 15) . ' error lainnya</em>' : '');
            }
            
        } else {
            $_SESSION['error'] = "Gagal membuka file CSV";
        }
    } else {
        $_SESSION['error'] = "File CSV tidak valid atau terlalu besar";
    }
    
    header('Location: peserta.php');
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

// Set page title
$page_title = 'Manajemen Peserta';

include 'includes/header-modern.php';
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
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                    <div class="float-right">
                        <button type="button" class="btn btn-success btn-sm mr-2" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload"></i> Upload CSV
                        </button>
                        <a href="download_template_csv.php" class="btn btn-info btn-sm mr-2">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahModal">
                            <i class="fas fa-plus"></i> Tambah Peserta
                        </button>
                    </div>
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
                                                data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#hapusModal<?= $p['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal Edit -->
                                <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Edit Peserta</h4>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_peserta" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Hapus -->
                                <div class="modal fade" id="hapusModal<?= $p['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <p>Apakah Anda yakin ingin menghapus peserta <strong><?= htmlspecialchars($p['nama_lengkap']) ?></strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
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

<!-- Modal Upload CSV -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h4 class="modal-title">Upload Peserta dari CSV</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Petunjuk Upload CSV:</strong>
                        <ul>
                            <li><strong>WAJIB:</strong> Download template CSV terlebih dahulu</li>
                            <li><strong>Format:</strong> File harus berformat .csv (bisa dari Excel atau Google Sheets)</li>
                            <li><strong>Encoding:</strong> File akan otomatis dikonversi ke UTF-8</li>
                            <li><strong>Delimiter:</strong> Bisa menggunakan koma (,) atau titik koma (;)</li>
                            <li><strong>Kolom Jenis Kelamin:</strong> L (Laki-laki) atau P (Perempuan)</li>
                            <li><strong>Kolom Status Aksi:</strong> aktif atau nonaktif</li>
                            <li><strong>Max Size:</strong> Maksimal 5MB</li>
                        </ul>
                    </div>
                    <div class="form-group">
                        <label>Pilih File CSV</label>
                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="upload_csv" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Peserta Baru</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_peserta" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer-modern.php'; ?>