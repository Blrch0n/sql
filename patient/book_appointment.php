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

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $message = "Огнооны формат буруу байна.";
        $messageType = "alert-error";
    } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        $message = "Цагийн формат буруу байна.";
        $messageType = "alert-error";
    } elseif ($date < date('Y-m-d')) {
        $message = "Өнгөрсөн огноогоор цаг авах боломжгүй.";
        $messageType = "alert-error";
    } elseif ($date == date('Y-m-d') && $time <= date('H:i:s')) {
        $message = "Өнгөрсөн цагаар захиалга хийх боломжгүй.";
        $messageType = "alert-error";
    } elseif ($doctor_id <= 0) {
        $message = "Эмчээ зөв сонгоно уу.";
        $messageType = "alert-error";
    } else {
        $doc_check = $conn->prepare("SELECT id FROM doctors WHERE id = ?");
        $doc_check->execute([$doctor_id]);
        if (!$doc_check->fetch()) {
            $message = "Сонгосон эмч олдсонгүй.";
            $messageType = "alert-error";
        } else {
            if (strlen($time) === 5) $time .= ':00';

            $dup_check = $conn->prepare("
                SELECT id FROM appointments 
                WHERE patient_id = :pid AND doctor_id = :did 
                AND appointment_date = :date 
                AND status IN ('pending', 'approved')
            ");
            $dup_check->execute([":pid" => $patient_id, ":did" => $doctor_id, ":date" => $date]);
            
            if ($dup_check->fetch()) {
                $message = "Та энэ эмчид энэ өдөр аль хэдийн цаг авсан байна.";
                $messageType = "alert-error";
            } else {
                $slot_check = $conn->prepare("
                    SELECT id FROM doctor_slots 
                    WHERE doctor_id = ? AND slot_date = ? AND slot_time = ? AND is_booked = 0
                ");
                $slot_check->execute([$doctor_id, $date, $time]);
                $available_slot = $slot_check->fetch();

                if (!$available_slot) {
                    $message = "Энэ цаг дээр эмчийн хуваарь байхгүй эсвэл захиалагдсан байна.";
                    $messageType = "alert-error";
                } else {
                    $conflict = $conn->prepare("
                        SELECT id FROM appointments 
                        WHERE doctor_id = :did AND appointment_date = :date 
                        AND appointment_time = :time AND status IN ('pending', 'approved')
                    ");
                    $conflict->execute([":did" => $doctor_id, ":date" => $date, ":time" => $time]);
                    
                    if ($conflict->fetch()) {
                        $message = "Энэ цаг аль хэдийн захиалагдсан байна.";
                        $messageType = "alert-error";
                    } else {
                        try {
                            $conn->beginTransaction();

                            $slotUpdate = $conn->prepare("
                                UPDATE doctor_slots 
                                SET is_booked = 1
                                WHERE id = :slot_id
                                  AND doctor_id = :doctor_id
                                  AND slot_date = :date
                                  AND slot_time = :time
                                  AND is_booked = 0
                            ");

                            $slotUpdate->execute([
                                ":slot_id" => $available_slot["id"],
                                ":doctor_id" => $doctor_id,
                                ":date" => $date,
                                ":time" => $time
                            ]);

                            if ($slotUpdate->rowCount() !== 1) {
                                throw new Exception("Энэ цаг аль хэдийн захиалагдсан байна.");
                            }

                            $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) 
                                    VALUES (:patient_id, :doctor_id, :date, :time, :reason, 'pending')";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([
                                ":patient_id" => $patient_id, 
                                ":doctor_id" => $doctor_id, 
                                ":date" => $date, 
                                ":time" => $time, 
                                ":reason" => $reason
                            ]);
                            
                            $conn->commit();
                            $message = "Цаг амжилттай захиалагдлаа! Эмч таны хүсэлтийг батлах хүртэл хүлээнэ үү.";
                            $messageType = "alert-success";
                        } catch (Exception $e) {
                            $conn->rollBack();
                            $message = $e->getMessage();
                            if(strpos($message, "Энэ цаг") === false) {
                                error_log("Book appointment error: " . $message);
                                $message = "Системийн алдаа гарлаа. Дахин оролдоно уу.";
                            }
                            $messageType = "alert-error";
                        }
                    }
                }
            }
        }
    }
}
?>

<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4" style="max-width: 600px; margin-left: auto; margin-right: auto;">
    <h2>Эмчид цаг авах</h2>
    
    <?php if ($message) echo "<div class='$messageType'>" . esc($message) . "</div>"; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
        
        <div class="form-group">
            <label>Эмч сонгох:</label>
            <select name="doctor_id" id="doctor_id" class="form-control" required>
                <option value="">-- Сонгох --</option>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?php echo $doctor['id']; ?>">
                        <?php echo esc($doctor['full_name'] . " - " . $doctor['department']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Огноо:</label>
            <input type="date" name="appointment_date" id="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label>Цаг (Сонгох боломжтой):</label>
            <select name="appointment_time" id="appointment_time" class="form-control" required>
                <option value="">-- Эхлээд эмч болон огноо сонгоно уу --</option>
            </select>
        </div>

        <div class="form-group">
            <label>Шалтгаан:</label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Ямар зовиуртай байгаагаа бичнэ үү..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary mt-4">Цаг авах</button>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const doctorSelect = document.getElementById("doctor_id");
    const dateInput = document.getElementById("appointment_date");
    const timeSelect = document.getElementById("appointment_time");

    function fetchAvailableTimes() {
        const doctorId = doctorSelect.value;
        const date = dateInput.value;

        if (doctorId && date) {
            fetch("get_slots.php?doctor_id=" + doctorId + "&date=" + date)
                .then(response => response.json())
                .then(slots => {
                    timeSelect.innerHTML = '<option value="">-- Цаг сонгох --</option>';
                    if (slots.length > 0) {
                        slots.forEach(slot => {
                            const option = document.createElement("option");
                            option.value = slot;
                            option.textContent = slot;
                            timeSelect.appendChild(option);
                        });
                    } else {
                        timeSelect.innerHTML = '<option value="">-- Сонгосон өдөр хуваарь алга --</option>';
                    }
                })
                .catch(error => {
                    console.error("Error fetching slots:", error);
                    timeSelect.innerHTML = '<option value="">-- Алдаа гарлаа --</option>';
                });
        } else {
            timeSelect.innerHTML = '<option value="">-- Эхлээд эмч болон огноо сонгоно уу --</option>';
        }
    }

    doctorSelect.addEventListener("change", fetchAvailableTimes);
    dateInput.addEventListener("change", fetchAvailableTimes);
});
</script>
