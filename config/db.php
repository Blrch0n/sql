<?php
$host = "localhost";
$dbname = "hospital_db";
$username = "hospital_app";
$password = "StrongPassword123!";

try {
    // Removed UNIX socket path to make it more universal, just using host/port approach
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Өгөгдлийн сантай холбогдоход алдаа гарлаа. Түр хүлээгээд дахин оролдоно уу.");
}
?>