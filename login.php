<?php
require_once "config/security.php";
require_once "config/db.php";

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') { header("Location: admin/dashboard.php"); }
    elseif ($role === 'doctor') { header("Location: doctor/dashboard.php"); }
    else { header("Location: patient/dashboard.php"); }
    exit;
}

$error = '';

if (isset($_SESSION['timeout_message'])) {
    $error = $_SESSION['timeout_message'];
    unset($_SESSION['timeout_message']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');

    $rate_error = check_rate_limit();
    if ($rate_error) {
        $error = $rate_error;
    } else {
        $email = trim($_POST["email"]);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        $password = $_POST["password"];

        if (empty($email) || empty($password)) {
            $error = "Имэйл болон нууц үгээ оруулна уу.";
        } else {
            $sql = "SELECT id, full_name, password, role FROM users WHERE email = :email LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([":email" => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password"])) {
                reset_login_attempts();

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                if ($user["role"] == "admin") {
                    header("Location: admin/dashboard.php");
                } elseif ($user["role"] == "doctor") {
                    header("Location: doctor/dashboard.php");
                } else {
                    header("Location: patient/dashboard.php");
                }
                exit;
            } else {
                record_failed_login();
                $error = "Имэйл эсвэл нууц үг буруу байна.";
            }
        }
    }
}
?>

<?php require_once "includes/header.php"; ?>

<div class="auth-container">
    <div class="glass-card auth-card">
        <h2 class="text-center">Нэвтрэх</h2>

        <?php if ($error) echo "<div class='alert-error'>" . esc($error) . "</div>"; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
            
            <div class="form-group">
                <label>Имэйл хаяг</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
            </div>

            <div class="form-group">
                <label>Нууц үг</label>
                <input type="password" name="password" class="form-control" placeholder="Нууц үгээ оруулна уу" required>
            </div>
            
            <button type="submit" class="btn btn-primary mt-4">Нэвтрэх</button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted">Бүртгэлгүй юу? <a href="register.php" class="text-primary">Бүртгүүлэх</a></p>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>