<?php
// Fungsi keamanan
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function encryptPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fungsi session
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function hasRole($roles) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    return in_array($_SESSION['role'], (array)$roles);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($roles) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    if (!in_array($_SESSION['role'], (array)$roles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

// Fungsi anti-cheat
function detectTabSwitch() {
    return "
    <script>
    let tabSwitchCount = 0;
    let startTime = Date.now();
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            tabSwitchCount++;
            if (tabSwitchCount > 3) {
                alert('Peringatan: Anda telah berpindah tab terlalu banyak kali. Ujian akan dihentikan.');
                window.location.href = 'force_submit.php';
            } else {
                alert('Peringatan: Jangan berpindah tab saat ujian berlangsung! (' + tabSwitchCount + '/3)');
            }
        }
    });
    
    // Blok F12, Ctrl+Shift+I, right click
    document.onkeydown = function(e) {
        if(e.keyCode == 123) {
            return false;
        }
        if(e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) {
            return false;
        }
        if(e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) {
            return false;
        }
        if(e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) {
            return false;
        }
        if(e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) {
            return false;
        }
    };
    
    document.addEventListener('contextmenu', event => event.preventDefault());
    
    // Blok copy, paste, select
    document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    document.addEventListener('copy', function(e) {
        e.preventDefault();
        return false;
    });
    
    document.addEventListener('paste', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Deteksi minimize
    window.addEventListener('blur', function() {
        tabSwitchCount++;
        if (tabSwitchCount > 3) {
            alert('Peringatan: Anda telah minimize terlalu banyak kali. Ujian akan dihentikan.');
            window.location.href = 'force_submit.php';
        } else {
            alert('Peringatan: Jangan minimize saat ujian berlangsung! (' + tabSwitchCount + '/3)');
        }
    });
    </script>
    ";
}

// Fungsi logging
function logActivity($user_id, $activity, $details = '') {
    global $pdo;
    
    // Handle case where user_id is 0 or null
    if ($user_id <= 0) {
        $user_id = null;
    }
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $activity, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}

// Fungsi pagination
function paginate($query, $params = [], $per_page = 10) {
    global $pdo;
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $per_page;
    
    $count_query = preg_replace('/SELECT .* FROM/i', 'SELECT COUNT(*) as total FROM', $query);
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    $query .= " LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    $total_pages = ceil($total / $per_page);
    
    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'total_pages' => $total_pages,
        'per_page' => $per_page
    ];
}

// Fungsi upload file
function uploadFile($file, $allowed_types, $max_size, $upload_dir) {
    if (!isset($file) || $file['error'] != 0) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File terlalu besar'];
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    $new_filename = uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $upload_path];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

// Fungsi generate kode acak
function generateRandomCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Fungsi format tanggal
function formatDate($date, $format = 'd-m-Y H:i') {
    return date($format, strtotime($date));
}

// Fungsi cek waktu ujian
function isExamTime($jadwal_mulai, $jadwal_selesai) {
    $now = date('Y-m-d H:i:s');
    return ($now >= $jadwal_mulai && $now <= $jadwal_selesai);
}
?>