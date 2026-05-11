<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    verify_csrf_token($_POST["csrf_token"] ?? '');

    $appointment_id = (int)$_POST['id'];
    $patient_id = $_SESSION["user_id"];

    $stmt = $conn->prepare("
        SELECT id, doctor_id, appointment_date, appointment_time, status 
        FROM appointments 
        WHERE id = :id AND patient_id = :patient_id
    ");
    $stmt->execute([":id" => $appointment_id, ":patient_id" => $patient_id]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        header("Location: my_appointments.php");
        exit;
    }

    if ($appointment['status'] !== 'pending') {
        $_SESSION['cancel_error'] = "Зөвхөн хүлээгдэж буй цагийг цуцлах боломжтой.";
        header("Location: my_appointments.php");
        exit;
    }

    $appt_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
    if (strtotime($appt_datetime) < time()) {
        $_SESSION['cancel_error'] = "Өнгөрсөн цагийг цуцлах боломжгүй.";
        header("Location: my_appointments.php");
        exit;
    }

    try {
        $conn->beginTransaction();

        $update = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id AND patient_id = :patient_id");
        $update->execute([":id" => $appointment_id, ":patient_id" => $patient_id]);

        $free_slot = $conn->prepare("
            UPDATE doctor_slots SET is_booked = 0 
            WHERE doctor_id = :did AND slot_date = :date AND slot_time = :time
        ");
        $free_slot->execute([
            ":did" => $appointment['doctor_id'],
            ":date" => $appointment['appointment_date'],
            ":time" => $appointment['appointment_time']
        ]);

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Cancel appointment error: " . $e->getMessage());
    }

    header("Location: my_appointments.php");
    exit;
} else {
    header("Location: my_appointments.php");
    exit;
}
?>