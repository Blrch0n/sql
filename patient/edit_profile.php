<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];

// Fetch current user info
$stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = :id");
$stmt->execute([':id' => $patient_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Хэрэглэгч олдсонгүй.");
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Аюулгүй байдлын алдаа! (CSRF)");
    }

    $full_name = sanitize_string($_POST['full_name'] ?? '');
    $phone = sanitize_string($_POST['phone'] ?? '');

    if (empty($full_name)) {
        $error = "Овог нэр хоосон байж болохгүй.";
    } elseif (!validate_name($full_name)) {
        $error = "Овог нэр зөвхөн үсэг, зай, цэг, зураас агуулсан байх ёстой.";
    } elseif (!empty($phone) && !validate_phone($phone)) {
        $error = "Утасны дугаарын формат буруу байна. (8-15 орон)";
    } else {
        $update_stmt = $conn->prepare("UPDATE users SET full_name = :name, phone = :phone WHERE id = :id");
        if ($update_stmt->execute([':name' => $full_name, ':phone' => $phone, ':id' => $patient_id])) {
            // Update session if name changed
            $_SESSION['full_name'] = $full_name;
            $_SESSION['success_msg'] = "Мэдээлэл амжилттай шинэчлэгдлээ.";
            header("Location: profile.php");
            exit();
        } else {
            $error = "Мэдээлэл шинэчлэхэд алдаа гарлаа.";
        }
    }
}

require_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card" style="padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); background: white; margin-top: 2rem;">
            <h2>Мэдээлэл засах</h2>
            <hr style="margin-bottom: 20px; border: 0; border-top: 1px solid #eee;">
            
            <?php if (!empty($error)) echo render_alert($error, 'danger'); ?>

            <form method="POST" action="">
                <?php echo render_csrf_field(); ?>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="full_name">Овог нэр *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo esc($user['full_name']); ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="email">Имэйл хаяг (Засах боломжгүй)</label>
                    <input type="email" id="email" class="form-control" value="<?php echo esc($user['email']); ?>" disabled style="background-color: #f8fafc; color: #64748b;">
                    <small style="color: #94a3b8;">Имэйл хаягаа солих шаардлагатай бол админ руу хандана уу.</small>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="phone">Утасны дугаар</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?php echo esc($user['phone'] ?? ''); ?>" placeholder="Утасны дугаар...">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Хадгалах</button>
                    <a href="profile.php" class="btn btn-secondary">Буцах</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>