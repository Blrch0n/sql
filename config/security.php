<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SESSION_TIMEOUT', 1800); 

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_message'] = "Таны хугацаа дууссан байна. Дахин нэвтэрнэ үү.";
    }
}
$_SESSION['last_activity'] = time();

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); 

function check_rate_limit() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
    }

    if (isset($_SESSION['lockout_until']) && time() > $_SESSION['lockout_until']) {
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['lockout_until']);
    }

    if (isset($_SESSION['lockout_until']) && time() < $_SESSION['lockout_until']) {
        $remaining = ceil(($_SESSION['lockout_until'] - time()) / 60);
        return "Хэт олон удаа оролдлоо. {$remaining} минутын дараа дахин оролдоно уу.";
    }

    return null; 
}

function record_failed_login() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['lockout_until'] = time() + LOGIN_LOCKOUT_TIME;
    }
}

function reset_login_attempts() {
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['lockout_until']);
    unset($_SESSION['first_attempt_time']);
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Хүсэлт хүчингүй боллоо. Хуудсаа дахин ачааллана уу.");
    }
}

/**
 * Require authentication with a specific role.
 * Redirects to login if not authenticated or wrong role.
 * 
 * @param string $role  The required role ('admin', 'doctor', 'patient')
 */
function require_auth($role) {
    $is_sub_dir = (basename(dirname($_SERVER['PHP_SELF'])) == 'admin' 
                || basename(dirname($_SERVER['PHP_SELF'])) == 'doctor' 
                || basename(dirname($_SERVER['PHP_SELF'])) == 'patient');
    $base_path = $is_sub_dir ? '../' : '';

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header("Location: {$base_path}login.php");
        exit;
    }
}


/**
 * Helper function for XSS protection (HTML escaping)
 */
function esc($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
