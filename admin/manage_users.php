<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$action_message = '';
$action_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_token($_POST["csrf_token"] ?? '');
    $patient_id = (int)$_POST['patient_id'];
    
    $check_role = $conn->prepare("SELECT role, is_active FROM users WHERE id = :id");
    $check_role->execute([":id" => $patient_id]);
    $user = $check_role->fetch();
    
    if (!$user || $user['role'] !== 'patient' || $user['is_active'] == 0) {
        $action_message = "Өвчтөн олдсонгүй эсвэл аль хэдийн устгагдсан байна.";
        $action_type = "alert-error";
    } else {
        $del = $conn->prepare("UPDATE users SET is_active = 0, deleted_at = NOW() WHERE id = :id AND role = 'patient'");
        $del->execute([":id" => $patient_id]);
        $action_message = "Өвчтөн амжилттай (soft) устгагдлаа.";
        $action_type = "alert-success";
    }
}

$stmt = $conn->query("
    SELECT u.id, u.full_name, u.email, u.created_at,
           COUNT(a.id) as appt_count
    FROM users u
    LEFT JOIN appointments a ON a.patient_id = u.id
    WHERE u.role = 'patient' AND u.is_active = 1
    GROUP BY u.id, u.full_name, u.email, u.created_at
    ORDER BY u.created_at DESC
");
$patients = $stmt->fetchAll();
?>
<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4">
    <h2>Бүртгэлтэй өвчтөнүүд</h2>
    
    <?php if ($action_message): ?>
        <div class="<?php echo esc($action_type); ?>" aria-live="polite"><?php echo esc($action_message); ?></div>
    <?php endif; ?>
    
    <div class="table-responsive mt-4">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Овог нэр</th>
                    <th>Имэйл</th>
                    <th>Бүртгүүлсэн огноо</th>
                    <th>Нийт захиалга</th>
                    <th>Үйлдэл</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($patients as $patient): ?>
                <tr>
                    <td><?php echo esc($patient['id']); ?></td>
                    <td><?php echo esc($patient['full_name']); ?></td>
                    <td><?php echo esc($patient['email']); ?></td>
                    <td><?php echo esc($patient['created_at']); ?></td>
                    <td><?php echo $patient['appt_count']; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="patient_id" value="<?php echo esc($patient['id']); ?>">
                            <button type="submit" class="btn btn-danger btn-small" 
                                onclick="return confirm('<?php echo esc($patient['full_name']); ?> өвчтөнийг устгахдаа итгэлтэй байна уу?');">Устгах</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($patients)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">Одоогоор өвчтөн бүртгэгдээгүй байна.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
