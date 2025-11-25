<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect ke halaman login jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect berdasarkan role user
if ($_SESSION['role'] == 'peserta') {
    header('Location: peserta/dashboard.php');
} else {
    header('Location: admin/dashboard.php');
}
?>