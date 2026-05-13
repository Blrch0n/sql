<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];

// Fetch the latest user info
$stmt = $conn->prepare("SELECT full_name, email, phone, created_at, updated_at, is_active FROM users WHERE id = :id");
$stmt->execute([':id' => $patient_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Хэрэглэгч олдсонгүй.");
}

require_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card" style="padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); background: white; margin-top: 2rem;">
            
            <?php 
                if (isset($_SESSION['success_msg'])) {
                    echo render_alert($_SESSION['success_msg'], 'success');
                    unset($_SESSION['success_msg']);
                }
                if (isset($_SESSION['error_msg'])) {
                    echo render_alert($_SESSION['error_msg'], 'danger');
                    unset($_SESSION['error_msg']);
                }
            ?>

            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1.5rem;">
                <h2 style="margin: 0;">Миний мэдээлэл</h2>
                <div>
                    <a href="edit_profile.php" class="btn btn-primary" style="margin-right: 10px;">Засах</a>
                    <a href="change_password.php" class="btn btn-secondary">Нууц үг солих</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="profile-info-group" style="margin-bottom: 15px;">
                    <label style="color: #64748b; font-size: 0.9em; font-weight: bold; text-transform: uppercase;">Овог нэр</label>
                    <div style="font-size: 1.1em; color: #1e293b; margin-top: 5px;"><?php echo esc($user['full_name']); ?></div>
                </div>

                <div class="profile-info-group" style="margin-bottom: 15px;">
                    <label style="color: #64748b; font-size: 0.9em; font-weight: bold; text-transform: uppercase;">Имэйл хаяг</label>
                    <div style="font-size: 1.1em; color: #1e293b; margin-top: 5px;"><?php echo esc($user['email']); ?></div>
                </div>

                <div class="profile-info-group" style="margin-bottom: 15px;">
                    <label style="color: #64748b; font-size: 0.9em; font-weight: bold; text-transform: uppercase;">Утасны дугаар</label>
                    <div style="font-size: 1.1em; color: #1e293b; margin-top: 5px;">
                        <?php echo !empty($user['phone']) ? esc($user['phone']) : '<em style="color:#94a3b8;">Оруулаагүй</em>'; ?>
                    </div>
                </div>

                <div class="profile-info-group" style="margin-bottom: 15px;">
                    <label style="color: #64748b; font-size: 0.9em; font-weight: bold; text-transform: uppercase;">Бүртгэл</label>
                    <div style="font-size: 1.1em; margin-top: 5px;">
                        <?php echo $user['is_active'] ? '<span style="color: #10b981; font-weight: bold;">Идэвхтэй</span>' : '<span style="color: #ef4444; font-weight: bold;">Идэвхгүй</span>'; ?>
                    </div>
                </div>

                <div class="profile-info-group" style="margin-bottom: 15px;">
                    <label style="color: #64748b; font-size: 0.9em; font-weight: bold; text-transform: uppercase;">Бүртгүүлсэн огноо</label>
                    <div style="font-size: 1.1em; color: #1e293b; margin-top: 5px;"><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></div>
                </div>

                <div class="profile-info-group" style="margin-bottom: 15px;">
                    <label style="color: #64748b; font-size: 0.9em; font-weight: bold; text-transform: uppercase;">Сүүлд шинэчилсэн</label>
                    <div style="font-size: 1.1em; color: #1e293b; margin-top: 5px;">
                        <?php echo $user['updated_at'] ? date('Y-m-d H:i', strtotime($user['updated_at'])) : '-'; ?>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>