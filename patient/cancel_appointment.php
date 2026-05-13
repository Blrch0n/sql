<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Аюулгүй байдлын алдаа байна (CSRF). Дахин оролдоно уу.";
        header("Location: my_appointments.php");
        exit;
    }

    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

    if ($appointment_id > 0) {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT slot_id, status FROM appointments WHERE id = :id AND patient_id = :pid");
            $stmt->execute([':id' => $appointment_id, ':pid' => $patient_id]);
            $appt = $stmt->fetch();

            if ($appt && in_array($appt['status'], ['pending', 'approved'])) {
                // Free the slot
                if ($appt['slot_id']) {
                    $free_slot = $conn->prepare("UPDATE doctor_slots SET is_booked = 0 WHERE id = :sid");
                    $free_slot->execute([':sid' => $appt['slot_id']]);
                }
                
                // Set appointment to cancelled and remove slot linkage (optional but good practice)
                $cancel_appt = $conn->prepare("UPDATE appointments SET status = 'cancelled', slot_id = NULL WHERE id = :id");
                $cancel_appt->execute([':id' => $appointment_id]);

                $conn->commit();
                $_SESSION['success_msg'] = "Цаг амжилттай цуцлагдлаа.";
            } else {
                throw new Exception("Энэ цагийг цуцлах боломжгүй эсвэл танд хандах эрх алга.");
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_msg'] = "Алдаа: " . $e->getMessage();
        }
    }
}
header("Location: my_appointments.php");
exit;
