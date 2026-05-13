<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id <= 0) {
    die("Буруу хандалт.");
}

$stmt = $conn->prepare("
    SELECT a.*, 
           u.full_name as doctor_name, 
           d.id as doctor_id,
           d.specialization, 
           d.phone as doctor_phone,
           dep.name as department_name 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN departments dep ON d.department_id = dep.id
    WHERE a.id = :id AND a.patient_id = :pid
");
$stmt->execute([':id' => $appointment_id, ':pid' => $patient_id]);
$appt = $stmt->fetch();

if (!$appt) {
    die("Цаг олдсонгүй эсвэл танд хандах эрх алга.");
}

$is_future = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) > time();
$can_modify = $is_future && in_array($appt['status'], ['pending', 'approved']);

require_once "../includes/header.php";
?>

<div class="row" style="margin-top: 2rem;">
    <div class="col-md-8 mx-auto">
        <div style="margin-bottom: 20px;">
            <a href="my_appointments.php" style="color: #64748b; text-decoration: none;">&larr; Буцах</a>
        </div>

        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 2rem; border: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">
                <div>
                    <h3 style="margin: 0 0 10px 0; color: #0f172a;">Цагийн дэлгэрэнгүй</h3>
                    <div style="color: #64748b; font-size: 0.9rem;">
                        <strong>Захиалга үүсгэсэн:</strong> <?php echo date('Y-m-d H:i', strtotime($appt['created_at'])); ?>
                    </div>
                </div>
                <div>
                    <?php echo render_status_badge($appt['status']); ?>
                </div>
            </div>

            <?php 
            if (isset($_SESSION['success_msg'])) {
                echo render_alert($_SESSION['success_msg'], 'success');
                unset($_SESSION['success_msg']);
            }
            if (isset($_SESSION['error_msg'])) {
                echo render_alert($_SESSION['error_msg'], 'danger');
                unset($_SESSION['error_msg']);
            }
            ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h5 style="color: #475569; margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">Цагийн мэдээлэл</h5>
                    <div style="margin-bottom: 10px;">
                        <span style="color: #64748b;">Огноо:</span><br>
                        <strong style="color: #0f172a; font-size: 1.1rem;"><?php echo date('Y-m-d', strtotime($appt['appointment_date'])); ?></strong>
                    </div>
                    <div>
                        <span style="color: #64748b;">Цаг:</span><br>
                        <strong style="color: #0f172a; font-size: 1.1rem;"><?php echo date('H:i', strtotime($appt['appointment_time'])); ?></strong>
                    </div>
                </div>

                <div style="background: #f0f9ff; padding: 15px; border-radius: 8px; border: 1px solid #bae6fd;">
                    <h5 style="color: #0369a1; margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #bae6fd; padding-bottom: 5px;">Эмчийн мэдээлэл</h5>
                    <div style="margin-bottom: 10px;">
                        <span style="color: #0284c7;">Нэр:</span><br>
                        <strong style="color: #0c4a6e;">Др. <?php echo esc($appt['doctor_name']); ?></strong>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="color: #0284c7;">Тасаг:</span><br>
                        <strong style="color: #0c4a6e;"><?php echo esc($appt['department_name']); ?></strong>
                    </div>
                    <div>
                        <span style="color: #0284c7;">Нарийн мэргэжил:</span><br>
                        <strong style="color: #0c4a6e;"><?php echo esc($appt['specialization']); ?></strong>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <h5 style="color: #475569; margin-bottom: 10px;">Зовиур, шалтгаан:</h5>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; min-height: 80px; color: #334155; border: 1px solid #e2e8f0;">
                    <?php echo nl2br(esc($appt['reason'] ?: 'Оруулаагүй байна.')); ?>
                </div>
            </div>

            <?php if ($can_modify): ?>
                <div style="display: flex; gap: 15px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <a href="reschedule_appointment.php?id=<?php echo $appt['id']; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">Цаг солих</a>
                    
                    <form method="POST" action="cancel_appointment.php" onsubmit="return confirm('Та энэ цагийг цуцлахдаа итгэлтэй байна уу?');" style="flex: 1;">
                        <?php echo render_csrf_field(); ?>
                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">Цуцлах</button>
                    </form>
                </div>
            <?php elseif ($appt['status'] == 'completed'): ?>
                <div style="text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <a href="review_appointment.php?id=<?php echo $appt['id']; ?>" class="btn btn-warning" style="padding: 10px 30px; font-weight: bold;">Эмчид үнэлгээ өгөх / Сэтгэгдэл үлдээх</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
