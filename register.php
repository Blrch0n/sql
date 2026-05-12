<?php
require_once "config/security.php";
require_once "config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (!empty($full_name) && !empty($email) && !empty($password)) {
        if (!validate_name($full_name) || strlen($full_name) > 100) {
            $error = "Бүртгэл амжилтгүй. Оруулсан мэдээллээ шалгана уу.";
        } elseif (!validate_email($email) || strlen($email) > 100) {
            $error = "Бүртгэл амжилтгүй. Оруулсан мэдээллээ шалгана уу.";
        } elseif (!validate_password($password)) {
            $error = "Бүртгэл амжилтгүй. Оруулсан мэдээллээ шалгана уу.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([":email" => $email]);
            if ($stmt->fetch()) {
                $error = "Бүртгэл амжилтгүй. Оруулсан мэдээллээ шалгана уу.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (full_name, email, password, role) 
                        VALUES (:full_name, :email, :password, 'patient')";

                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ":full_name" => $full_name,
                    ":email" => $email,
                    ":password" => $hashed_password
                ]);

                header("Location: login.php");
                exit;
            }
        }
    }
}
?>

<?php require_once "includes/header.php"; ?>

<div class="auth-container">
    <div class="glass-card auth-card">
        <h2 class="text-center">Өвчтөн бүртгүүлэх</h2>

        <?php if (isset($error)) echo "<div class='alert-error'>" . esc($error) . "</div>"; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
            
            <div class="form-group">
                <label>Овог нэр</label>
                <input type="text" name="full_name" class="form-control" placeholder="Жишээ: Бат Болд" required>
            </div>

            <div class="form-group">
                <label>Имэйл хаяг</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
            </div>
            
            <div class="form-group">
                <label>Нууц үг</label>
                <input type="password" name="password" class="form-control" placeholder="Тоо, үсэг, тусгай тэмдэгт орсон 8+ тэмдэгт" pattern="(?=.*\d)(?=.*[a-zA-Z])(?=.*[\W_]).{8,}" title="Хамгийн багадаа 8 тэмдэгт, 1 тоо, 1 үсэг болон 1 тусгай тэмдэгт (Жишээ нь: @$!%*?&) агуулсан байх ёстой." required>
            </div>
            
            <button type="submit" class="btn btn-primary mt-4">Бүртгүүлэх</button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted">Бүртгэлтэй юу? <a href="login.php" class="text-primary">Нэвтрэх</a></p>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>