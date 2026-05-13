<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$action_message = '';
$action_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_token($_POST["csrf_token"] ?? '');
    $doctor_db_id = (int)$_POST['doctor_id'];
    
    $doc_stmt = $conn->prepare("SELECT d.id, d.user_id FROM doctors d WHERE d.id = :did");
    $doc_stmt->execute([":did" => $doctor_db_id]);
    $doc = $doc_stmt->fetch();
    
    if (!$doc) {
        $action_message = "Эмч олдсонгүй.";
        $action_type = "alert-error";
    } else {
        $check = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = :did AND status IN ('pending', 'approved')");
        $check->execute([":did" => $doctor_db_id]);
        $active = $check->fetch()['cnt'];
        
        if ($active > 0) {
            $action_message = "Энэ эмчид {$active} идэвхтэй захиалга байна. Эхлээд тэдгээрийг цуцална уу.";
            $action_type = "alert-error";
        } else {
            $deactivate = $conn->prepare("UPDATE users SET is_active = 0, deleted_at = NOW() WHERE id = ? AND role = 'doctor'");
            $deactivate->execute([$doc['user_id']]);
            $action_message = "Эмч идэвхгүй болгогдлоо. Захиалгын түүх хадгалагдсан.";
            $action_type = "alert-success";
        }
    }
}

$stmt = $conn->query("
    SELECT d.id, u.full_name, u.email, dep.name as department, d.specialization, d.phone, u.is_active
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN departments dep ON d.department_id = dep.id
    ORDER BY u.is_active DESC, dep.name, u.full_name
");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4">
    <h2>Эмч нарын жагсаалт</h2>
    
    <?php if ($action_message): ?>
        <div class="<?php echo esc($action_type); ?>"><?php echo esc($action_message); ?></div>
    <?php endif; ?>
    
    <div class="table-responsive mt-4">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Нэр</th>
                    <th>И-мэйл</th>
                    <th>Тасаг</th>
                    <th>Мэргэшил</th>
                    <th>Утас</th>
                    <th>Үйлдэл</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($doctors as $doc): ?>
                <tr <?php echo !$doc['is_active'] ? 'style="opacity:0.55;"' : ''; ?>>
                    <td><?php echo esc($doc['id']); ?></td>
                    <td>
                        <?php echo esc($doc['full_name']); ?>
                        <?php if (!$doc['is_active']): ?>
                            <span class="badge badge-danger" style="margin-left:6px;font-size:0.7rem;">Идэвхгүй</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc($doc['email']); ?></td>
                    <td><?php echo esc($doc['department']); ?></td>
                    <td><?php echo esc($doc['specialization'] ?: '-'); ?></td>
                    <td><?php echo esc($doc['phone'] ?: '-'); ?></td>
                    <td>
                        <?php if ($doc['is_active']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="doctor_id" value="<?php echo esc($doc['id']); ?>">
                            <button type="submit" class="btn btn-danger btn-small"
                                onclick="return confirm('<?php echo esc($doc['full_name']); ?> эмчийг идэвхгүй болгохдоо итгэлтэй байна уу?');">Идэвхгүй болгох</button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:0.85rem;">Устгагдсан</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($doctors)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">Одоогоор эмч бүртгэгдээгүй байна.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>