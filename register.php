<?php
require_once "config/security.php";
require_once "config/db.php";

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? "admin/dashboard.php" : ($_SESSION['role'] == 'doctor' ? "doctor/dashboard.php" : "patient/dashboard.php")));
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF Token
    verify_csrf_token($_POST["csrf_token"] ?? '');

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Бүх талбарыг бөглөнө үү.";
    } elseif (!validate_name($full_name)) {
        $error = "Овог нэр зөвхөн үсэг байх ёстой.";
    } elseif (!validate_email($email)) {
        $error = "Имэйл хаяг буруу байна.";
    } elseif (!validate_password($password)) {
        $error = "Нууц үг дор хаяж 8 тэмдэгт, 1 тоо, 1 үсэг, 1 тусгай тэмдэгт агуулсан байх ёстой.";
    } elseif ($password !== $confirm_password) {
        $error = "Нууц үгнүүд тохирохгүй байна.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([":email" => $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Энэ имэйл хаяг бүртгэлтэй байна.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $sql = "INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, 'patient')";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute([
                ":full_name" => $full_name,
                ":email" => $email,
                ":password" => $hashed_password
            ])) {
                $success = "Амжилттай бүртгүүллээ! Одоо нэвтэрч орно уу.";
            } else {
                $error = "Бүртгэл үүсгэхэд алдаа гарлаа.";
            }
        }
    }
}
?>

<?php require_once "includes/header.php"; ?>

<div class="auth-container">
    <div class="glass-card auth-card">
        <h2 class="text-center">Бүртгүүлэх</h2>

        <?php if ($error) echo "<div class='alert-error' aria-live='polite'>" . esc($error) . "</div>"; ?>
        <?php if ($success) echo "<div class='alert-success' aria-live='polite'>" . esc($success) . " <br><a href='login.php'>Нэвтрэх</a></div>"; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="full_name">Овог нэр</label>
                <input type="text" id="full_name" name="full_name" class="form-control" autocomplete="name" required>
            </div>

            <div class="form-group">
                <label for="email">Имэйл хаяг</label>
                <input type="email" id="email" name="email" class="form-control" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Нууц үг</label>
                <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Нууц үг (давтах)</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" autocomplete="new-password" required>
            </div>
            
            <button type="submit" class="btn btn-primary mt-4">Бүртгүүлэх</button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted">Аль хэдийн бүртгэлтэй юу? <a href="login.php" class="text-primary">Нэвтрэх</a></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
