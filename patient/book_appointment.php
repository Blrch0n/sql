<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_once "../includes/notifications.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Preselect options from GET url params
$preselect_doc_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$preselect_date = isset($_GET['date']) ? sanitize_string($_GET['date']) : '';
$preselect_slot_id = isset($_GET['slot_id']) ? (int)$_GET['slot_id'] : 0;
$preselect_dep_id = 0;

if ($preselect_doc_id > 0) {
    $stmt = $conn->prepare("SELECT department_id FROM doctors WHERE id = :id");
    $stmt->execute([':id' => $preselect_doc_id]);
    $doc_dep = $stmt->fetch();
    if ($doc_dep) {
        $preselect_dep_id = $doc_dep['department_id'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $doctor_id = (int)$_POST["doctor_id"];
    $slot_id = (int)$_POST["slot_id"];
    $reason = sanitize_string($_POST["reason"]);

    // Validate doctor and slot
    if ($doctor_id > 0 && $slot_id > 0) {
        try {
            $conn->beginTransaction();

            // Lock the slot row and check availability
            $stmt = $conn->prepare("SELECT slot_date, slot_time, is_booked FROM doctor_slots WHERE id = :sid AND doctor_id = :did FOR UPDATE");
            $stmt->execute([':sid' => $slot_id, ':did' => $doctor_id]);
            $slot = $stmt->fetch();

            if (!$slot || $slot['is_booked']) {
                throw new Exception("Энэ цаг өөр хүнд захиалагдсан байна эсвэл олдсонгүй.");
            }

            if (!is_future_datetime($slot['slot_date'], $slot['slot_time'])) {
                throw new Exception("Энэ цаг өнгөрсөн байна. Ирээдүйн цаг сонгоно уу.");
            }

            // Prevent duplicate booking on same slot
            $check_dup = $conn->prepare("SELECT id FROM appointments WHERE patient_id = :pid AND slot_id = :sid");
            $check_dup->execute([':pid' => $patient_id, ':sid' => $slot_id]);
            if ($check_dup->fetch()) {
                throw new Exception("Та энэ цагийг аль хэдийн захиалсан байна.");
            }

            // Prevent booking the same doctor twice on the same day
            $check_same_day = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = :pid AND doctor_id = :did AND appointment_date = :date AND status NOT IN ('cancelled')");
            $check_same_day->execute([':pid' => $patient_id, ':did' => $doctor_id, ':date' => $slot['slot_date']]);
            if ($check_same_day->fetchColumn() > 0) {
                throw new Exception("Та энэ өдөр энэ эмчтэй аль хэдийн цаг захиалсан байна.");
            }

            // Atomically mark slot as booked — rowCount guards against concurrent booking
            $update_slot = $conn->prepare("UPDATE doctor_slots SET is_booked = 1 WHERE id = :id AND is_booked = 0");
            $update_slot->execute([':id' => $slot_id]);
            if ($update_slot->rowCount() !== 1) {
                throw new Exception("Энэ цаг өөр хүнд захиалагдсан байна. Өөр цаг сонгоно уу.");
            }

                // Create appointment entry
                $insert_appt = $conn->prepare("
                    INSERT INTO appointments (patient_id, doctor_id, slot_id, appointment_date, appointment_time, reason, status)
                    VALUES (:pid, :did, :sid, :date, :time, :reason, 'pending')
                    ");
                
                $insert_appt->execute([
                    ':pid' => $patient_id,
                    ':did' => $doctor_id,
                    ':sid' => $slot_id,
                    ':date' => $slot['slot_date'],
                    ':time' => $slot['slot_time'],
                    ':reason' => $reason
                ]);

                // Notify patient that booking request was sent
                $doc_date = date('Y-m-d', strtotime($slot['slot_date']));
                $doc_time = date('H:i', strtotime($slot['slot_time']));
                create_notification($conn, $patient_id, "Цаг захиалга илгээгдлээ", "Таны $doc_date $doc_time цагийн захиалга эмчид хүлээлгэн өгөгдлөө. Батлагдахыг хүлээнэ үү.");

                $conn->commit();
                $success = "Цаг амжилттай захиалагдлаа! Эмч рүү зөвшөөрөх хүсэлт илгээгдлээ.";
                
                // Clear preselects
                $preselect_doc_id = $preselect_slot_id = $preselect_dep_id = 0;
                $preselect_date = '';
                
            } catch (Exception $e) {
                $conn->rollBack();
                $error = $e->getMessage();
            }
        } else {
            $error = "Эмч болон цагийг заавал сонгоно уу.";
        }
    }
}

// Fetch all departments
$deps = $conn->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

require_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-9 mx-auto">
        <h2 style="margin-top: 1rem; margin-bottom: 2rem; color: #1e293b;">Шинэ цаг авах</h2>
        
        <?php if (!empty($error)): ?>
            <?php echo render_alert($error, 'danger'); ?>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <?php echo render_alert($success, 'success'); ?>
            <div style="text-align: center; margin: 20px 0;">
                <a href="my_appointments.php" class="btn btn-primary" style="margin-right: 15px;">Миний цагууд харах</a>
                <a href="book_appointment.php" class="btn btn-secondary">Дахин цаг авах</a>
            </div>
        <?php else: ?>

        <form method="POST" action="">
            <?php echo render_csrf_field(); ?>
            <input type="hidden" name="slot_id" id="selected_slot_id" value="<?php echo $preselect_slot_id > 0 ? $preselect_slot_id : ''; ?>">

            <div class="booking-stepper">
                
                <!-- Step 1: Department -->
                <div class="step-card" id="step-1" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border-left: 4px solid #0284c7;">
                    <h4 style="margin-top: 0;">1. Тасаг сонгох</h4>
                    <select id="department_id" class="form-control" style="max-width: 400px;">
                        <option value="">Тасаг сонгох...</option>
                        <?php foreach ($deps as $dep): ?>
                            <option value="<?php echo $dep['id']; ?>" <?php echo $preselect_dep_id == $dep['id'] ? 'selected' : ''; ?>>
                                <?php echo esc($dep['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 2: Doctor -->
                <div class="step-card" id="step-2" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border-left: 4px solid #0ea5e9; opacity: 0.5; pointer-events: none; transition: all 0.3s;">
                    <h4 style="margin-top: 0;">2. Эмч сонгох</h4>
                    <select id="doctor_id" name="doctor_id" class="form-control" style="max-width: 400px;" data-preselect="<?php echo $preselect_doc_id > 0 ? $preselect_doc_id : ''; ?>">
                        <option value="">Эхлээд тасаг сонгоно уу</option>
                    </select>
                </div>

                <!-- Step 3: Date & Slots -->
                <div class="step-card" id="step-3" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border-left: 4px solid #38bdf8; opacity: 0.5; pointer-events: none; transition: all 0.3s;">
                    <h4 style="margin-top: 0;">3. Огноо ба цаг сонгох</h4>
                    <div style="margin-bottom: 15px;">
                        <input type="date" id="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" style="max-width: 250px;" value="<?php echo esc($preselect_date ?: date('Y-m-d')); ?>">
                    </div>
                    
                    <div id="time-slots-container" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 80px;">
                        <div style="color: #94a3b8;">Эмч болон огноо сонгохыг хүлээнэ үү...</div>
                    </div>
                </div>

                <!-- Step 4: Summary & Confirm -->
                <div class="step-card" id="step-4" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border-left: 4px solid #7dd3fc; opacity: 0.5; pointer-events: none; transition: all 0.3s;">
                    <h4 style="margin-top: 0;">4. Дэлгэрэнгүй баталгаажуулах</h4>
                    
                    <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div><strong>Тасаг:</strong> <span id="summary-dep" style="color:#0f172a;">-</span></div>
                        <div><strong>Эмч:</strong> <span id="summary-doc" style="color:#0f172a;">-</span></div>
                        <div><strong>Огноо:</strong> <span id="summary-date" style="color:#0f172a;">-</span></div>
                        <div><strong>Цаг:</strong> <span id="summary-time" style="color:#0f172a; font-weight: bold;">-</span></div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label for="reason" style="font-weight: bold; margin-bottom: 5px; display: block;">Зовиур, шалтгаан (Нэмэлт мэдээлэл)</label>
                        <textarea name="reason" id="reason" rows="3" class="form-control" placeholder="Таныг эмчид үзүүлэхэд хэрэгтэй нэмэлт мэдээлэл байвал энд бичнэ үү..."></textarea>
                    </div>

                    <button type="submit" id="submit-booking" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem; border-radius: 8px;" disabled>Цаг баталгаажуулж илгээх</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/booking.js"></script>

<?php require_once "../includes/footer.php"; ?>
