<?php
$host = "localhost";
$dbname = "hospital_db";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:unix_socket=/opt/lampp/var/mysql/mysql.sock;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Өгөгдлийн сантай холбогдоход алдаа гарлаа. Түр хүлээгээд дахин оролдоно уу.");
}
?>