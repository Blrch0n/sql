<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_once "../includes/notifications.php";
require_auth('doctor');

$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = :uid LIMIT 1");
$stmt->execute([":uid" => $_SESSION["user_id"]]);
$doctor_row = $stmt->fetch();
$doctor_id = $doctor_row ? $doctor_row['id'] : 0;
if (!$doctor_id) {
    die("Эмчийн мэдээлэл олдсонгүй.");
}

$message = '';
$messageType = '';

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'alert-success';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');

    $appointment_id = (int)$_POST["appointment_id"];
    $status = $_POST["status"];

    $valid_transitions = [
        'pending'   => ['approved', 'cancelled'],
        'approved'  => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    try {
        $conn->beginTransaction();

        // Fetch current status inside transaction with row lock to prevent TOCTOU
        $cur_stmt = $conn->prepare("SELECT status, slot_id FROM appointments WHERE id = :id AND doctor_id = :did FOR UPDATE");
        $cur_stmt->execute([':id' => $appointment_id, ':did' => $doctor_id]);
        $current_row = $cur_stmt->fetch();
        $current = $current_row ? $current_row['status'] : null;

        if ($current && isset($valid_transitions[$current]) && in_array($status, $valid_transitions[$current])) {
            if ($status === 'cancelled' && $current_row['slot_id']) {
                $conn->prepare("UPDATE doctor_slots SET is_booked = 0 WHERE id = :sid")
                     ->execute([':sid' => $current_row['slot_id']]);
            }

            // Fetch patient_id and appointment date for notification
            $appt_info = $conn->prepare("SELECT patient_id, appointment_date, appointment_time FROM appointments WHERE id = :id");
            $appt_info->execute([':id' => $appointment_id]);
            $appt_data = $appt_info->fetch();

            $conn->prepare("UPDATE appointments SET status = :status WHERE id = :id AND doctor_id = :did")
                 ->execute([":status" => $status, ":id" => $appointment_id, ":did" => $doctor_id]);

            // Notify patient about status change
            if ($appt_data) {
                $appt_date = date('Y-m-d', strtotime($appt_data['appointment_date']));
                $appt_time = date('H:i', strtotime($appt_data['appointment_time']));
                $titles = [
                    'approved'  => 'Цаг баталгаажлаа',
                    'cancelled' => 'Цаг цуцлагдлаа',
                    'completed' => 'Үзлэг дууслаа',
                ];
                $msgs = [
                    'approved'  => "$appt_date $appt_time цагийн захиалга эмчид баталгаажлаа.",
                    'cancelled' => "$appt_date $appt_time цагийн захиалгыг эмч цуцлав.",
                    'completed' => "$appt_date $appt_time цагийн үзлэг дууссан гэж тэмдэглэгдлээ.",
                ];
                if (isset($titles[$status])) {
                    create_notification($conn, $appt_data['patient_id'], $titles[$status], $msgs[$status]);
                }
            }

            $conn->commit();
            $_SESSION['flash_message'] = "Цаг $status төлөвт шилжлээ!";
            $_SESSION['flash_type'] = "alert-success";
        } else {
            $conn->rollBack();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['flash_message'] = "Алдаа гарлаа: " . $e->getMessage();
        $_SESSION['flash_type'] = "alert-error";
    }

    header("Location: my_appointments.php");
    exit();
}

$appointments = $conn->prepare("
    SELECT a.id, u.full_name AS patient_name, a.appointment_date, a.appointment_time, a.reason, a.status 
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = :doctor_id
    ORDER BY a.appointment_date, a.appointment_time
");
$appointments->execute([":doctor_id" => $doctor_id]);
$appointments = $appointments->fetchAll();
?>

<?php require_once "../includes/header.php"; ?>
<div class="glass-card page-card" style="margin-top: 2rem;">
    <h2 class="section-title">Миний цагууд</h2>
    
    <?php if ($message): ?>
        <div class="<?php echo esc($messageType); ?>"><?php echo esc($message); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
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
                        <td><?php echo esc($app['patient_name']); ?></td>
                        <td><?php echo esc($app['appointment_date']); ?></td>
                        <td><?php echo esc($app['appointment_time']); ?></td>
                        <td><?php echo esc($app['reason']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo esc($app['status']); ?>">
                                <?php echo esc($app['status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                <select name="status" class="form-control" style="width: auto; display: inline-block; padding: 0.25rem; height: auto;" onchange="if(confirm('Төлвийг өөрчлөхдөө итгэлтэй байна уу?')) this.form.submit(); else this.value='<?php echo esc($app['status']); ?>';">
                                    <?php if($app['status'] == 'pending'): ?>
                                    <option value="pending" selected>Хүлээгдэж буй</option>
                                    <option value="approved">Батлах</option>
                                    <option value="cancelled">Цуцлах</option>
                                    <?php elseif($app['status'] == 'approved'): ?>
                                    <option value="approved" selected>Баталгаажсан</option>
                                    <option value="completed">Дууссан</option>
                                    <option value="cancelled">Цуцлах</option>
                                    <?php else: ?>
                                    <option value="<?php echo esc($app['status']); ?>" selected><?php echo esc($app['status']); ?></option>
                                    <?php endif; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
