<?php
require_once "config/security.php";

// 1. Session хувьсагчдыг цэвэрлэх
$_SESSION = array();

// 2. Browser тал дахь Session Cookie-г устгах (Best practice)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Сервер талын Session файлыг устгах
session_destroy();

header("Location: login.php");
exit;
?>