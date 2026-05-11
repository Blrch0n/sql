<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$sql = "
    SELECT appointments.*, users.full_name AS doctor_name, departments.name AS department
    FROM appointments
    JOIN doctors ON appointments.doctor_id = doctors.id
    JOIN users ON doctors.user_id = users.id
    JOIN departments ON doctors.department_id = departments.id
    WHERE appointments.patient_id = :patient_id
    ORDER BY appointment_date DESC, appointment_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([":patient_id" => $_SESSION["user_id"]]);

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_labels = [
    'pending'   => ['text' => 'Хүлээгдэж буй', 'class' => 'badge-warning'],
    'approved'  => ['text' => 'Баталгаажсан', 'class' => 'badge-success'],
    'rejected'  => ['text' => 'Татгалзсан', 'class' => 'badge-rejected'],
    'cancelled' => ['text' => 'Цуцлагдсан', 'class' => 'badge-danger'],
    'completed' => ['text' => 'Дууссан', 'class' => 'badge-completed'],
];
?>

<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4">
    <h2>Миний авсан цагууд</h2>

    <?php if (isset($_SESSION['cancel_error'])): ?>
        <div class="alert-error"><?php echo esc($_SESSION['cancel_error']); ?></div>
        <?php unset($_SESSION['cancel_error']); ?>
    <?php endif; ?>

    <div class="table-responsive mt-4">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Эмч</th>
                    <th>Тасаг</th>
                    <th>Огноо</th>
                    <th>Цаг</th>
                    <th>Шалтгаан</th>
                    <th>Төлөв</th>
                    <th>Үйлдэл</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app): ?>
                    <?php 
                        $status = $app['status'];
                        $label = $status_labels[$status] ?? ['text' => $status, 'class' => 'badge-warning'];
                        $is_future = strtotime($app['appointment_date'] . ' ' . $app['appointment_time']) > time();
                        $can_cancel = ($status === 'pending' && $is_future);
                    ?>
                    <tr>
                        <td><?php echo esc($app["doctor_name"]); ?></td>
                        <td><?php echo esc($app["department"]); ?></td>
                        <td><?php echo esc($app["appointment_date"]); ?></td>
                        <td><?php echo esc(substr($app["appointment_time"], 0, 5)); ?></td>
                        <td><?php echo esc($app["reason"] ?: '-'); ?></td>
                        <td>
                            <span class="badge <?php echo $label['class']; ?>"><?php echo $label['text']; ?></span>
                            <?php if ($status === 'rejected' && !empty($app['rejection_reason'])): ?>
                                <br><small class="text-muted"><?php echo esc($app['rejection_reason']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($can_cancel): ?>
                                <form action="cancel_appointment.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo esc($app['id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Та энэ цагийг цуцлахдаа итгэлтэй байна уу?');">Цуцлах</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($appointments)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Та одоогоор цаг аваагүй байна.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>