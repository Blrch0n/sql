<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (isset($_POST['appointment_id'])) {
    $appointment_id = (int)$_POST['appointment_id'];
}

$error = '';
$success = '';

// Check ownership and state
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as doctor_name 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.id = :id AND a.patient_id = :pid
");
$stmt->execute([':id' => $appointment_id, ':pid' => $patient_id]);
$appt = $stmt->fetch();

if (!$appt) {
    die("Цаг олдсонгүй.");
}

if ($appt['status'] !== 'completed') {
    die("Зөвхөн дууссан үзлэгт үнэлгээ өгөх боломжтой.");
}

// Check if review already exists
$check_review = $conn->prepare("SELECT id, rating, comment FROM doctor_reviews WHERE appointment_id = :id");
$check_review->execute([':id' => $appointment_id]);
$existing_review = $check_review->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$existing_review) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF алдаа.";
    } else {
        $rating = (int)$_POST['rating'];
        $comment = sanitize_string($_POST['comment'] ?? '');

        if ($rating >= 1 && $rating <= 5) {
            $insert = $conn->prepare("
                INSERT INTO doctor_reviews (appointment_id, patient_id, doctor_id, rating, comment)
                VALUES (:app_id, :pid, :did, :rating, :comment)
            ");
            
            if ($insert->execute([
                ':app_id' => $appointment_id,
                ':pid' => $patient_id,
                ':did' => $appt['doctor_id'],
                ':rating' => $rating,
                ':comment' => $comment
            ])) {
                $_SESSION['success_msg'] = "Таны үнэлгээ амжилттай бүртгэгдлээ. Баярлалаа!";
                header("Location: appointment_detail.php?id=" . $appointment_id);
                exit();
            } else {
                $error = "Үнэлгээ хадгалахад алдаа гарлаа.";
            }
        } else {
            $error = "Үнэлгээ 1-ээс 5 хооронд байх ёстой.";
        }
    }
}

require_once "../includes/header.php";
?>

<div class="row" style="margin-top: 2rem;">
    <div class="col-md-6 mx-auto">
        <div style="margin-bottom: 20px;">
            <a href="appointment_detail.php?id=<?php echo $appointment_id; ?>" style="color: #64748b; text-decoration: none;">&larr; Буцах</a>
        </div>

        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 2rem; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; color: #1e293b;">Эмчид үнэлгээ өгөх</h3>
            <p style="color: #64748b; margin-bottom: 20px;">Та <strong>Др. <?php echo esc($appt['doctor_name']); ?></strong> эмчид үзүүлсэн (<?php echo date('Y-m-d', strtotime($appt['appointment_date'])); ?>) үзлэгийнхээ үнэлгээг үлдээнэ үү.</p>
            
            <?php if (!empty($error)) echo render_alert($error, 'danger'); ?>

            <?php if ($existing_review): ?>
                <div class="alert alert-info">
                    <strong>Та энэ үзлэгт аль хэдийн үнэлгээ өгсөн байна.</strong><br><br>
                    Төрөл: <?php echo str_repeat('★', $existing_review['rating']) . str_repeat('☆', 5 - $existing_review['rating']); ?><br>
                    Сэтгэгдэл: <?php echo nl2br(esc($existing_review['comment'] ?: '-')); ?>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <?php echo render_csrf_field(); ?>
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; margin-bottom: 10px; display: block;">Одоор үнэлэх (1-5)</label>
                        <select name="rating" class="form-control" required style="max-width: 150px; font-size: 1.2rem;">
                            <option value="5">★★★★★ (5/5)</option>
                            <option value="4">★★★★☆ (4/5)</option>
                            <option value="3">★★★☆☆ (3/5)</option>
                            <option value="2">★★☆☆☆ (2/5)</option>
                            <option value="1">★☆☆☆☆ (1/5)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="comment">Сэтгэгдэл (заавал биш)</label>
                        <textarea name="comment" id="comment" rows="4" class="form-control" placeholder="Та эмчийн харилцаа, үйлчилгээний талаар сэтгэгдлээ үлдээж болно..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning" style="width: 100%; font-weight: bold; padding: 12px;">Үнэлгээг илгээх</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
