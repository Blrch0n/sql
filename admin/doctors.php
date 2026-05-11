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
            try {
                $conn->beginTransaction();
                $conn->prepare("DELETE FROM doctors WHERE id = ?")->execute([$doctor_db_id]);
                $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'")->execute([$doc['user_id']]);
                $conn->commit();
                $action_message = "Эмч амжилттай устгагдлаа.";
                $action_type = "alert-success";
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Delete doctor error: " . $e->getMessage());
                $action_message = "Алдаа гарлаа.";
                $action_type = "alert-error";
            }
        }
    }
}

$stmt = $conn->query("
    SELECT d.id, u.full_name, u.email, dep.name as department, d.specialization, d.phone 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    JOIN departments dep ON d.department_id = dep.id
    ORDER BY dep.name, u.full_name
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
                <tr>
                    <td><?php echo esc($doc['id']); ?></td>
                    <td><?php echo esc($doc['full_name']); ?></td>
                    <td><?php echo esc($doc['email']); ?></td>
                    <td><?php echo esc($doc['department']); ?></td>
                    <td><?php echo esc($doc['specialization'] ?: '-'); ?></td>
                    <td><?php echo esc($doc['phone'] ?: '-'); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="doctor_id" value="<?php echo esc($doc['id']); ?>">
                            <button type="submit" class="btn btn-danger btn-small" 
                                onclick="return confirm('<?php echo esc($doc['full_name']); ?> эмчийг устгахдаа итгэлтэй байна уу?');">Устгах</button>
                        </form>
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