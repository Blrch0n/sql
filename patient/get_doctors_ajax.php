<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

if ($department_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT d.id, u.full_name, d.specialization 
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE d.department_id = :dep_id AND u.is_active = 1
    ORDER BY u.full_name ASC
");
$stmt->execute([':dep_id' => $department_id]);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($doctors);