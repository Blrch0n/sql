<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('doctor');

$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = :uid LIMIT 1");
$stmt->execute([":uid" => $_SESSION["user_id"]]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Эмчийн мэдээлэл олдсонгүй.");
}
$doctor_id = $doctor['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    verify_csrf_token($_POST["csrf_token"] ?? '');
    
    $appointment_id = (int)$_POST['appointment_id'];
    $action = $_POST['action'];
    
    try {
        $conn->beginTransaction();
        
        // Fetch to ensure it belongs to the doctor
        $stmt = $conn->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE id = :id AND doctor_id = :doctor_id FOR UPDATE");
        $stmt->execute([":id" => $appointment_id, ":doctor_id" => $doctor_id]);
        $app = $stmt->fetch();
        
        if (!$app) {
            $conn->rollBack();
            header("Location: my_appointments.php");
            exit;
        }

        if ($action === 'approve') {
            $new_status = 'approved';
            $update = $conn->prepare("UPDATE appointments SET status = :status WHERE id = :id");
            $update->execute([":status" => $new_status, ":id" => $appointment_id]);
        } elseif ($action === 'cancel') {
            $new_status = 'cancelled';
            $update = $conn->prepare("UPDATE appointments SET status = :status WHERE id = :id");
            $update->execute([":status" => $new_status, ":id" => $appointment_id]);
            
            // Free the slot
            $free_slot = $conn->prepare("
                UPDATE doctor_slots 
                SET is_booked = 0 
                WHERE doctor_id = :did AND slot_date = :date AND slot_time = :time
            ");
            $free_slot->execute([
                ":did" => $doctor_id,
                ":date" => $app['appointment_date'],
                ":time" => $app['appointment_time']
            ]);
        } else {
            $conn->rollBack();
            header("Location: my_appointments.php");
            exit;
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Status update error: " . $e->getMessage());
    }
    
    header("Location: my_appointments.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason, a.created_at,
           u.full_name AS patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = :doctor_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([":doctor_id" => $doctor_id]);
$appointments = $stmt->fetchAll();
?>

<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4">
    <h2>Миний цагууд</h2>

    <div class="table-responsive mt-4">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Өвчтөн</th>
                    <th>Огноо</th>
                    <th>Цаг</th>
                    <th>Шалтгаан</th>
                    <th>Төлөв</th>
                    <th>Үйлдэл</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app): ?>
                    <tr>
                        <td><?php echo esc($app["patient_name"]); ?></td>
                        <td><?php echo esc($app["appointment_date"]); ?></td>
                        <td><?php echo esc($app["appointment_time"]); ?></td>
                        <td><?php echo esc($app["reason"] ?: '-'); ?></td>
                        <td>
                            <?php 
                                $status = $app['status'];
                                if ($status == 'pending') {
                                    echo "<span class='badge badge-warning'>Хүлээгдэж буй</span>";
                                } elseif ($status == 'approved') {
                                    echo "<span class='badge badge-success'>Баталгаажсан</span>";
                                } elseif ($status == 'cancelled') {
                                    echo "<span class='badge badge-danger'>Цуцлагдсан</span>";
                                }
                            ?>
                        </td>
                        <td>
                            <?php if ($app["status"] == "pending"): ?>
                                <form action="my_appointments.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                    <input type="hidden" name="appointment_id" value="<?php echo esc($app['id']); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-small btn-success">Батлах</button>
                                </form>
                                <form action="my_appointments.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                    <input type="hidden" name="appointment_id" value="<?php echo esc($app['id']); ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Та энэ цагийг цуцлахдаа итгэлтэй байна уу?');">Цуцлах</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Одоогоор цаг захиалга байхгүй байна.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
