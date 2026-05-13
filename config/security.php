<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', 1);
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none';");

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

function check_rate_limit($email, $ip, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = :email AND ip_address = :ip AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([':email' => $email, ':ip' => $ip]);
    $count = $stmt->fetchColumn();
    if ($count >= MAX_LOGIN_ATTEMPTS) {
        return "Хэт олон удаа алдаатай нэвтрэх оролдлого гарлаа. 15 минутын дараа дахин оролдоно уу.";
    }
    return null;
}

function record_failed_login($email, $ip, $conn) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)");
    $stmt->execute([':email' => $email, ':ip' => $ip]);
}

function reset_login_attempts($email, $ip, $conn) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE email = :email AND ip_address = :ip");
    $stmt->execute([':email' => $email, ':ip' => $ip]);
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Хүсэлт хүчингүй боллоо. Хуудсаа дахин ачааллана уу.");
    }
    return true;
}

function require_auth($role) {
    $is_sub_dir = (basename(dirname($_SERVER['PHP_SELF'])) == 'admin' 
                || basename(dirname($_SERVER['PHP_SELF'])) == 'doctor' 
                || basename(dirname($_SERVER['PHP_SELF'])) == 'patient');
    $base_path = $is_sub_dir ? '../' : '';

    $roles = is_array($role) ? $role : [$role];

    if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
        session_unset();
        session_destroy();
        header("Location: {$base_path}login.php");
        exit;
    }
}

function esc($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function sanitize_string($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function sanitize_id($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false ? (int)$id : 0;
}

function clean_input($data) {
    $data = trim($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
function validate_required($value) { return !empty(trim($value)); }
function validate_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function validate_username($username) { return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username); }
function validate_password($password) { return preg_match('/^(?=.*\d)(?=.*[a-zA-Z])(?=.*[\W_]).{8,}$/', $password); }
function validate_integer_id($id) { return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0; }
function validate_date($date) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date); }
function validate_time($time) { return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time); }
function validate_name($name) { return preg_match('/^[\p{L}\s\.\-]+$/u', $name); }
function validate_phone($phone) { return preg_match('/^\+?[0-9]{8,15}$/', $phone); }

function is_future_datetime($date, $time = '00:00') {
    try {
        $dt = new DateTime("$date $time");
        return $dt > new DateTime();
    } catch (Exception $e) {
        return false;
    }
}

function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }

function redirect_if_not_logged_in() {
    $is_sub_dir = (basename(dirname($_SERVER['PHP_SELF'])) == 'admin' 
                || basename(dirname($_SERVER['PHP_SELF'])) == 'doctor' 
                || basename(dirname($_SERVER['PHP_SELF'])) == 'patient');
    $base_path = $is_sub_dir ? '../' : '';

    if (!isset($_SESSION['user_id'])) {
        header("Location: {$base_path}login.php");
        exit;
    }
}
function require_role($role) { require_auth($role); }
