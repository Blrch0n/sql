<?php
$host = getenv('DB_HOST') ?: "localhost";
$dbname = getenv('DB_NAME') ?: "hospital_db";
$username = getenv('DB_USER');
$password = getenv('DB_PASS');

if ($username === false || $password === false) {
    throw new RuntimeException("Database configuration error: Missing required environment variables (DB_USER, DB_PASS).");
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Өгөгдлийн сантай холбогдоход алдаа гарлаа. Түр хүлээгээд дахин оролдоно уу.");
}
