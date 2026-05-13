<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$date = isset($_GET['date']) ? sanitize_string($_GET['date']) : '';

header('Content-Type: application/json');

if ($doctor_id <= 0 || empty($date)) {
    echo json_encode(['error' => 'Эмч эсвэл огноо буруу байна.']);
    exit;
}

// Ensure the date is valid and >= today
if ($date < date('Y-m-d')) {
    echo json_encode(['error' => 'Өнгөрсөн огноо сонгох боломжгүй.']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT id, slot_time as time, is_booked 
        FROM doctor_slots 
        WHERE doctor_id = :did AND slot_date = :date AND is_booked = 0
        ORDER BY slot_time ASC
    ");
    
    $stmt->execute([
        ':did' => $doctor_id,
        ':date' => $date
    ]);
    
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out past times if date is today
    if ($date == date('Y-m-d')) {
        $currentTime = date('H:i:s');
        $slots = array_filter($slots, function($slot) use ($currentTime) {
            return $slot['time'] > $currentTime;
        });
        // Reindex array
        $slots = array_values($slots);
    }

    echo json_encode(['slots' => $slots]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Мэдээлэл авахад алдаа гарлаа.']);
}
