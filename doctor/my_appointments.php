<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('doctor');

$doctor_id = get_doctor_id($_SESSION["user_id"], $conn);
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
    
    if (in_array($status, ['approved', 'completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status = :status WHERE id = :id AND doctor_id = :doctor_id");
        $stmt->execute([":status" => $status, ":id" => $appointment_id, ":doctor_id" => $doctor_id]);
        
        $_SESSION['flash_message'] = "Цаг $status төлөвт шилжлээ!";
        $_SESSION['flash_type'] = "alert-success";
        header("Location: my_appointments.php");
        exit();
    }
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
                                <select name="status" class="form-control" style="width: auto; display: inline-block; padding: 0.25rem; height: auto;" onchange="this.form.submit()">
                                    <option value="pending" <?php if($app['status'] == 'pending') echo 'selected'; ?>>Хүлээгдэж буй</option>
                                    <option value="approved" <?php if($app['status'] == 'approved') echo 'selected'; ?>>Батлах</option>
                                    <option value="completed" <?php if($app['status'] == 'completed') echo 'selected'; ?>>Дууссан</option>
                                    <option value="cancelled" <?php if($app['status'] == 'cancelled') echo 'selected'; ?>>Цуцлах</option>
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
