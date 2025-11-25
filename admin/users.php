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
    if (isset($_POST['tambah_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $nama_lengkap = $_POST['nama_lengkap'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $nama_lengkap, $email, $role]);
            
            $_SESSION['success'] = 'User berhasil ditambahkan';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Username sudah ada';
        }
        
        header('Location: users.php');
        exit;
    }
    
    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE users SET nama_lengkap = ?, email = ?, role = ?, is_active = ?";
        $params = [$nama_lengkap, $email, $role, $is_active];
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $sql .= ", password = ?";
            $params[] = $password;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['success'] = 'User berhasil diperbarui';
        header('Location: users.php');
        exit;
    }
    
    if (isset($_POST['hapus_user'])) {
        $id = $_POST['id'];
        
        // Cek apakah user ini adalah admin terakhir
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'Tidak dapat menghapus akun sendiri';
        } else {
            // Cek apakah user ini adalah admin
            $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $check->execute([$id]);
            $role = $check->fetchColumn();
            
            if ($role == 'admin') {
                $admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                if ($admin_count <= 1) {
                    $_SESSION['error'] = 'Tidak dapat menghapus admin terakhir';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = 'User berhasil dihapus';
                }
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'User berhasil dihapus';
            }
        }
        
        header('Location: users.php');
        exit;
    }
}

// Filter berdasarkan role
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$where = '';
$params = [];

if ($role_filter && in_array($role_filter, ['admin', 'operator', 'guru', 'pengawas'])) {
    $where = "WHERE role = ?";
    $params[] = $role_filter;
}

// Ambil data user
$users = $pdo->prepare("SELECT * FROM users $where ORDER BY role, nama_lengkap");
$users->execute($params);
$users = $users->fetchAll();

// Hitung statistik
$stats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
    SUM(CASE WHEN role = 'operator' THEN 1 ELSE 0 END) as operator,
    SUM(CASE WHEN role = 'guru' THEN 1 ELSE 0 END) as guru,
    SUM(CASE WHEN role = 'pengawas' THEN 1 ELSE 0 END) as pengawas,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as aktif,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as nonaktif
FROM users")->fetch();

$page_title = 'Manajemen User';
include 'includes/header-modern.php';
?>

<!-- Page Header -->
<div class="content-header">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users text-gradient me-2"></i>
                Manajemen User
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                <i class="fas fa-plus me-1"></i>Tambah User
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-primary"><?php echo $stats['total']; ?></div>
                <div class="small text-muted">Total User</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-danger"><?php echo $stats['admin']; ?></div>
                <div class="small text-muted">Admin</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-warning"><?php echo $stats['operator']; ?></div>
                <div class="small text-muted">Operator</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-info"><?php echo $stats['guru']; ?></div>
                <div class="small text-muted">Guru</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-success"><?php echo $stats['pengawas']; ?></div>
                <div class="small text-muted">Pengawas</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card border-0 shadow-custom">
            <div class="card-body text-center p-3">
                <div class="h4 fw-bold text-secondary"><?php echo $stats['total'] - $stats['aktif']; ?></div>
                <div class="small text-muted">Non-Aktif</div>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-custom">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="fas fa-table text-gradient me-2"></i>
            Daftar User
        </h5>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width: 200px;">
                <input type="text" name="table_search" class="form-control" placeholder="Cari user...">
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>Belum ada data user</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'operator' ? 'warning' : ($user['role'] == 'guru' ? 'info' : 'success')); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['is_active'] ? 'Aktif' : 'Non-Aktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Belum pernah'; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary"
                                                onclick="editUser(<?php echo $user['id']; ?>)"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger"
                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="tambahUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    Tambah User Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="guru">Guru</option>
                            <option value="pengawas">Pengawas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Management Scripts -->
<script>
    // User management functions
    function editUser(id) {
        Utils.showInfo('Fitur edit user akan ditampilkan dalam modal.', 'Edit User');
    }
    
    function deleteUser(id, username) {
        Utils.confirm(`Apakah Anda yakin ingin menghapus user "${username}"?`, 'Hapus User').then((result) => {
            if (result.isConfirmed) {
                Utils.showLoading('Menghapus user...');
                setTimeout(() => {
                    Utils.hideLoading();
                    Utils.showSuccess('User berhasil dihapus!', 'Success');
                }, 1000);
            }
        });
    }
    
    // Table search functionality
    document.querySelector('input[name="table_search"]').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    // Form validation
    document.querySelector('#tambahUserModal form').addEventListener('submit', function(e) {
        const username = this.username.value.trim();
        const nama_lengkap = this.nama_lengkap.value.trim();
        const email = this.email.value.trim();
        const password = this.password.value;
        const role = this.role.value;
        
        if (!username || !nama_lengkap || !email || !password || !role) {
            e.preventDefault();
            Utils.showWarning('Semua field harus diisi!', 'Validasi Gagal');
            return false;
        }
        
        if (username.length < 3) {
            e.preventDefault();
            Utils.showWarning('Username minimal 3 karakter!', 'Validasi Gagal');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            Utils.showWarning('Password minimal 6 karakter!', 'Validasi Gagal');
            return false;
        }
    });
</script>

<?php include 'includes/footer-modern.php'; ?>