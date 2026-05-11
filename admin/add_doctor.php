<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$success = "";
$error = "";

$stmt = $conn->query("SELECT * FROM departments");
$departments = $stmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $department_id = (int)$_POST["department_id"];
    $specialization = trim($_POST["specialization"]);
    $phone = trim($_POST["phone"]);

    if (empty($full_name) || empty($email) || empty($password) || empty($department_id)) {
        $error = "Бүх талбарыг бөглөнө үү!";
    } elseif (strlen($password) < 8) {
        $error = "Нууц үг хамгийн багадаа 8 тэмдэгт байх ёстой.";
    } else {
        try {
            $conn->beginTransaction();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'doctor')");
            $stmt->execute([$full_name, $email, $hashed_password]);
            
            $user_id = $conn->lastInsertId();

            $stmt2 = $conn->prepare("INSERT INTO doctors (user_id, department_id, specialization, phone) VALUES (?, ?, ?, ?)");
            $stmt2->execute([$user_id, $department_id, $specialization, $phone]);

            $conn->commit();
            $success = "Эмч амжилттай бүртгэгдлээ!";
        } catch(PDOException $e) {
            $conn->rollBack();
            error_log("Add doctor error: " . $e->getMessage());
            if ($e->getCode() == 23000) { $error = "Email давхцаж байна."; }
            else { $error = "Системийн алдаа гарлаа. Дахин оролдоно уу."; }
        }
    }
}
?>
<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4" style="max-width: 600px; margin: 0 auto;">
    <h2>Эмч нэмэх</h2>
    
    <?php if($error) echo "<div class='alert-error'>" . esc($error) . "</div>"; ?>
    <?php if($success) echo "<div class='alert-success'>" . esc($success) . "</div>"; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
        
        <div class="form-group">
            <label>Бүтэн нэр:</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>И-мэйл:</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Нууц үг:</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Тасаг (Department):</label>
            <select name="department_id" class="form-control" required>
                <option value="">Сонгох...</option>
                <?php foreach($departments as $dept): ?>
                    <option value="<?php echo esc($dept['id']); ?>"><?php echo esc($dept['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Мэргэшил (Specialization):</label>
            <input type="text" name="specialization" class="form-control">
        </div>
        
        <div class="form-group">
            <label>Утас:</label>
            <input type="text" name="phone" class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary mt-4">Эмч нэмэх</button>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>