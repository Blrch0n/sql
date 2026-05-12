<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$doctors = $conn->query("
    SELECT doctors.id, users.full_name, departments.name AS department
    FROM doctors
    JOIN users ON doctors.user_id = users.id
    JOIN departments ON doctors.department_id = departments.id
    ORDER BY departments.name, users.full_name
")->fetchAll();

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');

    $patient_id = $_SESSION["user_id"];
    $doctor_id = (int)$_POST["doctor_id"];
    $date = $_POST["appointment_date"] ?? '';
    $time = $_POST["appointment_time"] ?? '';
    $reason = trim($_POST["reason"] ?? '');

    if ($doctor_id <= 0) {
        $message = "Эмч сонгоно уу.";
        $messageType = "alert-error";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $message = "Огнооны формат буруу байна.";
        $messageType = "alert-error";
    } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        $message = "Цагийн формат буруу байна.";
        $messageType = "alert-error";
    } elseif (mb_strlen($reason) > 500) {
        $message = "Шалтгаан хэт урт байна (дээд тал нь 500 тэмдэгт).";
        $messageType = "alert-error";
    } elseif (!is_future_datetime($date, $time)) {
       $message = "Өнгөрсөн цагт захиалах боломжгүй.";
       $messageType = "alert-error";
    } else {
        // Option duplicate rule check
        $dup_check = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = :pid AND doctor_id = :did AND appointment_date = :adate AND status IN ('pending', 'approved')");
        $dup_check->execute([':pid' => $patient_id, ':did' => $doctor_id, ':adate' => $date]);
        if ($dup_check->fetchColumn() > 0) {
            $message = "Та энэ өдөр энэ эмчид аль хэдийн цаг захиалсан байна.";
            $messageType = "alert-error";
        } else {
            try {
                $conn->beginTransaction();

                // Select the slot_id first!
                $slotStmt = $conn->prepare("SELECT id FROM doctor_slots WHERE doctor_id = :doctor_id AND slot_date = :date AND slot_time = :time AND is_booked = 0 FOR UPDATE");
                $slotStmt->execute([
                    ':doctor_id' => $doctor_id,
                    ':date' => $date,
                    ':time' => $time
                ]);
                $slot_id = $slotStmt->fetchColumn();

                if (!$slot_id) {
                    throw new Exception("Энэ цаг аль хэдийн захиалагдсан байна эсвэл олдсонгүй.");
                }

                $slotUpdate = $conn->prepare("
                    UPDATE doctor_slots
                    SET is_booked = 1
                    WHERE id = :slot_id AND is_booked = 0
                ");
                $slotUpdate->execute([':slot_id' => $slot_id]);

                if ($slotUpdate->rowCount() !== 1) {
                    throw new Exception("Энэ цаг аль хэдийн захиалагдсан байна эсвэл олдсонгүй.");
                }

                $sql = "INSERT INTO appointments (patient_id, doctor_id, slot_id, appointment_date, appointment_time, reason, status) 
                        VALUES (:patient_id, :doctor_id, :slot_id, :date, :time, :reason, 'pending')";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ":patient_id" => $patient_id, 
                    ":doctor_id" => $doctor_id, 
                    ":slot_id" => $slot_id,
                    ":date" => $date, 
                    ":time" => $time, 
                    ":reason" => $reason
                ]);
                
                $conn->commit();
                $message = "Цаг амжилттай захиалагдлаа! Эмч таны хүсэлтийг батлах хүртэл хүлээнэ үү.";
                $messageType = "alert-success";
            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $msg = $e->getMessage();
                if (strpos($msg, "аль хэдийн захиалагдсан") !== false) {
                    $message = $msg;
                } else {
                    error_log("Book appointment error: " . $msg);
                    $message = "Системийн алдаа гарлаа. Дахин оролдоно уу.";
                }
                $messageType = "alert-error";
            }
        }
    }
}
?>

<?php require_once "../includes/header.php"; ?>
<div class="glass-card page-card" style="max-width: 600px; margin: 2rem auto;">
    <h2 class="section-title">Цаг захиалах (Бүртгэл)</h2>
    
    <?php if ($message): ?>
        <div class="<?php echo esc($messageType); ?>" aria-live="polite"><?php echo esc($message); ?></div>
        <?php if ($messageType == 'alert-success') $message = ''; ?>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
        
        <div class="form-group">
            <label for="doctor_id">Эмч сонгох:</label>
            <select name="doctor_id" id="doctor_id" class="form-control" required>
                <option value="">-- Сонгоно уу --</option>
                <?php foreach ($doctors as $doc): ?>
                    <option value="<?php echo $doc['id']; ?>">
                        <?php echo esc($doc['full_name']) . " (" . esc($doc['department']) . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="appointment_date">Огноо:</label>
            <input type="date" name="appointment_date" id="appointment_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
            <label for="appointment_time">Цаг:</label>
            <input type="time" name="appointment_time" id="appointment_time" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="reason">Шалтгаан (заавал биш):</label>
            <textarea name="reason" id="reason" class="form-control" rows="3" maxlength="500"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Захиалах</button>
    </form>
</div>
<?php require_once "../includes/footer.php"; ?>
