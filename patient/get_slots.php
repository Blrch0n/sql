<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

header('Content-Type: application/json');

if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
    echo json_encode([]);
    exit;
}

$doctor_id = (int)$_GET['doctor_id'];
$date = $_GET['date'];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $doctor_id <= 0) {
    echo json_encode([]);
    exit;
}

if ($date < date('Y-m-d')) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT slot_time 
    FROM doctor_slots 
    WHERE doctor_id = ? AND slot_date = ? AND is_booked = 0
    ORDER BY slot_time ASC
");
$stmt->execute([$doctor_id, $date]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

$options = [];
$now = date('H:i:s');
$is_today = ($date === date('Y-m-d'));

foreach ($slots as $slot) {
    if ($is_today && $slot['slot_time'] <= $now) {
        continue;
    }
    $options[] = substr($slot['slot_time'], 0, 5);
}

echo json_encode($options);
?>