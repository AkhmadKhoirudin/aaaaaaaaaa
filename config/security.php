<?php
// Security Configuration for CBT Application

// Anti-Cheat Settings
define('MAX_VIOLATIONS', 3);
define('SCREENSHOT_INTERVAL', 30); // seconds
define('FACE_DETECTION_INTERVAL', 5); // seconds
define('CONNECTION_CHECK_INTERVAL', 10); // seconds

// Session Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_REGENERATE_INTERVAL', 300); // 5 minutes

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'ogg']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'avi', 'mov']);

// Security Headers
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com; font-src \'self\' fonts.gstatic.com cdnjs.cloudflare.com; img-src \'self\' data:; connect-src \'self\';');
}

// Rate Limiting
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . $identifier;
    $attempts = isset($_SESSION[$key]) ? $_SESSION[$key] : 0;
    
    if ($attempts >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key] = $attempts + 1;
    
    if ($attempts === 0) {
        $_SESSION[$key . '_time'] = time();
    }
    
    return true;
}

// Device Fingerprinting
function generateDeviceFingerprint() {
    $fingerprint = '';
    
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $fingerprint .= $_SERVER['HTTP_USER_AGENT'];
    }
    
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $fingerprint .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }
    
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
        $fingerprint .= $_SERVER['HTTP_ACCEPT_ENCODING'];
    }
    
    return md5($fingerprint);
}

// IP Validation
function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

// CSRF Token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Password Policy
function validatePassword($password) {
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return false;
    }
    
    return true;
}

// Input Sanitization
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// XSS Prevention
function preventXSS($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// SQL Injection Prevention (using PDO prepared statements is recommended)
function validateInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
        default:
            return sanitizeInput($input);
    }
}

// Log Security Events
function logSecurityEvent($event, $details = '') {
    $logFile = '../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    
    $logEntry = "[$timestamp] [$ip] [$userAgent] $event: $details" . PHP_EOL;
    
    if (!is_dir('../logs')) {
        mkdir('../logs', 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Initialize security
setSecurityHeaders();
generateCSRFToken();
?>