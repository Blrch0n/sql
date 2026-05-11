<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');
    $action = $_POST['action'] ?? 'add';
    
    if ($action === 'add') {
        $department_name = trim($_POST["department_name"] ?? '');

        if (empty($department_name)) {
            $error = "Тасгийн нэрийг оруулна уу!";
        } elseif (strlen($department_name) > 100) {
            $error = "Тасгийн нэр 100 тэмдэгтээс хэтрэхгүй байх ёстой.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
                $stmt->execute([$department_name]);
                $success = "Тасаг амжилттай нэмэгдлээ!";
            } catch(PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Энэ нэртэй тасаг аль хэдийн бүртгэлтэй байна.";
                } else {
                    $error = "Алдаа гарлаа.";
                }
            }
        }
    } elseif ($action === 'delete') {
        $dept_id = (int)($_POST['dept_id'] ?? 0);
        
        $check = $conn->prepare("SELECT COUNT(*) as cnt FROM doctors WHERE department_id = :did");
        $check->execute([":did" => $dept_id]);
        $linked = $check->fetch()['cnt'];
        
        if ($linked > 0) {
            $error = "Энэ тасагт {$linked} эмч бүртгэлтэй байна. Эхлээд эмч нарыг шилжүүлэх эсвэл устгана уу.";
        } else {
            $del = $conn->prepare("DELETE FROM departments WHERE id = ?");
            $del->execute([$dept_id]);
            if ($del->rowCount() > 0) {
                $success = "Тасаг амжилттай устгагдлаа.";
            } else {
                $error = "Тасаг олдсонгүй.";
            }
        }
    }
}

$stmt = $conn->query("
    SELECT d.*, COUNT(doc.id) as doctor_count 
    FROM departments d 
    LEFT JOIN doctors doc ON doc.department_id = d.id 
    GROUP BY d.id, d.name 
    ORDER BY d.name ASC
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4" style="max-width: 600px; margin: 0 auto;">
    <h2>Тасаг нэмэх</h2>
    
    <?php if($error) echo "<div class='alert-error'>" . esc($error) . "</div>"; ?>
    <?php if($success) echo "<div class='alert-success'>" . esc($success) . "</div>"; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>Тасгийн нэр:</label>
            <input type="text" name="department_name" class="form-control" maxlength="100" required>
        </div>
        <button type="submit" class="btn btn-primary mt-4">Тасаг нэмэх</button>
    </form>
</div>

<div class="glass-card mt-4" style="max-width: 600px; margin: 0 auto;">
    <h3>Одоо байгаа тасгууд</h3>
    
    <?php if (empty($departments)): ?>
        <p class="text-muted mt-4">Одоогоор тасаг бүртгэгдээгүй байна.</p>
    <?php else: ?>
        <div class="table-responsive mt-4">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Тасгийн нэр</th>
                        <th>Эмч нарын тоо</th>
                        <th>Үйлдэл</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($departments as $dept): ?>
                    <tr>
                        <td><?php echo esc($dept['name']); ?></td>
                        <td><?php echo $dept['doctor_count']; ?></td>
                        <td>
                            <?php if ($dept['doctor_count'] == 0): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small" 
                                        onclick="return confirm('<?php echo esc($dept['name']); ?> тасгийг устгахдаа итгэлтэй байна уу?');">Устгах</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted" title="Эмч нартай тасгийг устгах боломжгүй">🔒</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>