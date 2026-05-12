<?php
require_once "config/security.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

verify_csrf_token($_POST["csrf_token"] ?? '');

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params["path"],
        'domain' => $params["domain"],
        'secure' => $params["secure"],
        'httponly' => $params["httponly"],
        'samesite' => 'Strict'
    ]);
}

session_destroy();

header("Location: login.php");
exit;
