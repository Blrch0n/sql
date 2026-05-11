<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$action_message = '';
$action_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    verify_csrf_token($_POST["csrf_token"] ?? '');
    
    $appointment_id = (int)$_POST['appointment_id'];
    $action = $_POST['action'];
    
    $appt_stmt = $conn->prepare("SELECT id, doctor_id, appointment_date, appointment_time, status FROM appointments WHERE id = :id");
    $appt_stmt->execute([":id" => $appointment_id]);
    $appt = $appt_stmt->fetch();
    
    if (!$appt) {
        $action_message = "Захиалга олдсонгүй.";
        $action_type = "alert-error";
    } else {
        if ($action === 'approve' && $appt['status'] === 'pending') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'approved' WHERE id = :id");
            $stmt->execute([":id" => $appointment_id]);
            $action_message = "Захиалга баталгаажлаа.";
            $action_type = "alert-success";
            
        } elseif ($action === 'cancel' && in_array($appt['status'], ['pending', 'approved'])) {
            try {
                $conn->beginTransaction();
                $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id");
                $stmt->execute([":id" => $appointment_id]);
                
                $free = $conn->prepare("UPDATE doctor_slots SET is_booked = 0 WHERE doctor_id = :did AND slot_date = :date AND slot_time = :time");
                $free->execute([":did" => $appt['doctor_id'], ":date" => $appt['appointment_date'], ":time" => $appt['appointment_time']]);
                
                $conn->commit();
                $action_message = "Захиалга цуцлагдлаа. Цаг чөлөөлөгдлөө.";
                $action_type = "alert-success";
            } catch (PDOException $e) {
                $conn->rollBack();
                $action_message = "Алдаа гарлаа.";
                $action_type = "alert-error";
            }
            
        } elseif ($action === 'complete' && $appt['status'] === 'approved') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE id = :id");
            $stmt->execute([":id" => $appointment_id]);
            $action_message = "Үзлэг дууссан гэж тэмдэглэгдлээ.";
            $action_type = "alert-success";
        } else {
            $action_message = "Энэ үйлдлийг гүйцэтгэх боломжгүй.";
            $action_type = "alert-error";
        }
    }
    
    $filter = $_GET['status'] ?? 'all';
    header("Location: appointments.php?status=" . urlencode($filter) . "&msg=" . urlencode($action_message) . "&mt=" . urlencode($action_type));
    exit;
}

if (isset($_GET['msg']) && !empty($_GET['msg'])) {
    $action_message = $_GET['msg'];
    $action_type = $_GET['mt'] ?? 'alert-success';
}

$status_filter = $_GET['status'] ?? 'all';
$valid_statuses = ['all', 'pending', 'approved', 'rejected', 'cancelled', 'completed'];
if (!in_array($status_filter, $valid_statuses)) $status_filter = 'all';

$sql = "
    SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason, a.rejection_reason,
           pu.full_name as patient_name, du.full_name as doctor_name, dep.name as department
    FROM appointments a 
    JOIN users pu ON a.patient_id = pu.id 
    JOIN doctors d ON a.doctor_id = d.id 
    JOIN users du ON d.user_id = du.id
    JOIN departments dep ON d.department_id = dep.id
";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " WHERE a.status = :status";
    $params[":status"] = $status_filter;
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

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
    <h2>Бүх цаг захиалга</h2>
    
    <?php if ($action_message): ?>
        <div class="<?php echo esc($action_type); ?>"><?php echo esc($action_message); ?></div>
    <?php endif; ?>

    <div class="filter-bar">
        <label>Шүүлтүүр:</label>
        <select onchange="window.location.href='appointments.php?status='+this.value">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Бүгд (<?php echo count($appointments); ?>)</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Хүлээгдэж буй</option>
            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Баталгаажсан</option>
            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Дууссан</option>
            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Татгалзсан</option>
            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Цуцлагдсан</option>
        </select>
    </div>
    
    <div class="table-responsive mt-4">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Өвчтөн</th>
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
                <?php foreach($appointments as $app): ?>
                <?php 
                    $status = $app['status'];
                    $label = $status_labels[$status] ?? ['text' => $status, 'class' => 'badge-warning'];
                ?>
                <tr>
                    <td><?php echo esc($app['id']); ?></td>
                    <td><?php echo esc($app['patient_name']); ?></td>
                    <td><?php echo esc($app['doctor_name']); ?></td>
                    <td><?php echo esc($app['department']); ?></td>
                    <td><?php echo esc($app['appointment_date']); ?></td>
                    <td><?php echo esc(substr($app['appointment_time'], 0, 5)); ?></td>
                    <td><?php echo esc($app['reason'] ?: '-'); ?></td>
                    <td>
                        <span class="badge <?php echo $label['class']; ?>"><?php echo $label['text']; ?></span>
                    </td>
                    <td>
                        <div class="action-buttons">
                        <?php if ($status === 'pending'): ?>
                            <form action="appointments.php?status=<?php echo esc($status_filter); ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                <input type="hidden" name="appointment_id" value="<?php echo esc($app['id']); ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-small">Батлах</button>
                            </form>
                            <form action="appointments.php?status=<?php echo esc($status_filter); ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                <input type="hidden" name="appointment_id" value="<?php echo esc($app['id']); ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Цуцлахдаа итгэлтэй байна уу?');">Цуцлах</button>
                            </form>
                        <?php elseif ($status === 'approved'): ?>
                            <form action="appointments.php?status=<?php echo esc($status_filter); ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                <input type="hidden" name="appointment_id" value="<?php echo esc($app['id']); ?>">
                                <input type="hidden" name="action" value="complete">
                                <button type="submit" class="btn btn-info btn-small">Дууссан</button>
                            </form>
                            <form action="appointments.php?status=<?php echo esc($status_filter); ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                <input type="hidden" name="appointment_id" value="<?php echo esc($app['id']); ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Цуцлахдаа итгэлтэй байна уу?');">Цуцлах</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($appointments)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted">Одоогоор цаг захиалга байхгүй байна.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>