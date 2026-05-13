<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_once "../includes/notifications.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

    if ($appointment_id > 0) {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT slot_id, status FROM appointments WHERE id = :id AND patient_id = :pid FOR UPDATE");
            $stmt->execute([':id' => $appointment_id, ':pid' => $patient_id]);
            $appt = $stmt->fetch();

            if ($appt && in_array($appt['status'], ['pending', 'approved'])) {
                // Free the slot
                if ($appt['slot_id']) {
                    $free_slot = $conn->prepare("UPDATE doctor_slots SET is_booked = 0 WHERE id = :sid");
                    $free_slot->execute([':sid' => $appt['slot_id']]);
                }

                $cancel_appt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id");
                $cancel_appt->execute([':id' => $appointment_id]);

                create_notification($conn, $patient_id, "Цаг цуцлагдлаа", "Таны цаг захиалга амжилттай цуцлагдлаа.");

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
