<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Аюулгүй байдлын алдаа! (CSRF)");
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Бүх талбарыг бөглөнө үү.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Шинэ нууц үг тохирохгүй байна.";
    } elseif (!validate_password($new_password)) {
        $error = "Шинэ нууц үг дор хаяж 8 тэмдэгт, 1 тоо, 1 үсэг, 1 тусгай тэмдэгт агуулсан байх ёстой.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $patient_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = :pwd WHERE id = :id");
            if ($update_stmt->execute([':pwd' => $hashed_password, ':id' => $patient_id])) {
                $_SESSION['success_msg'] = "Нууц үг амжилттай солигдлоо.";
                header("Location: profile.php");
                exit();
            } else {
                $error = "Нууц үг солиход алдаа гарлаа.";
            }
        } else {
            $error = "Одоогийн нууц үг буруу байна.";
        }
    }
}

require_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card" style="padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); background: white; margin-top: 2rem;">
            <h2>Нууц үг солих</h2>
            <hr style="margin-bottom: 20px; border: 0; border-top: 1px solid #eee;">
            
            <?php if (!empty($error)) echo render_alert($error, 'danger'); ?>

            <form method="POST" action="">
                <?php echo render_csrf_field(); ?>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="current_password">Одоогийн нууц үг *</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="new_password">Шинэ нууц үг *</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                    <small style="color: #64748b;">Дор хаяж 8 тэмдэгт байх.</small>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="confirm_password">Шинэ нууц үг давтах *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-warning">Нууц үг солих</button>
                    <a href="profile.php" class="btn btn-secondary">Буцах</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>