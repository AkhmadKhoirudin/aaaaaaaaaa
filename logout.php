<?php
session_start();

// Log activity before logout
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    logActivity($_SESSION['user_id'], 'logout', 'User logout');
}

// Destroy all session data
session_unset();
session_destroy();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header('Location: login.php');
exit();
?>