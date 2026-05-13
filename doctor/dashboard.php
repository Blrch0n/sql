<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('doctor');

$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = :uid LIMIT 1");
$stmt->execute([":uid" => $_SESSION["user_id"]]);
$doctor = $stmt->fetch();
$doctor_id = $doctor ? $doctor['id'] : 0;

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = :did AND appointment_date = CURDATE() AND status != 'cancelled'");
$stmt->execute([":did" => $doctor_id]);
$today_count = $stmt->fetch()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = :did AND status = 'pending'");
$stmt->execute([":did" => $doctor_id]);
$pending_count = $stmt->fetch()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT patient_id) as cnt FROM appointments WHERE doctor_id = :did");
$stmt->execute([":did" => $doctor_id]);
$patient_count = $stmt->fetch()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = :did AND status = 'approved'");
$stmt->execute([":did" => $doctor_id]);
$approved_count = $stmt->fetch()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = :did AND status = 'completed'");
$stmt->execute([":did" => $doctor_id]);
$completed_count = $stmt->fetch()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM doctor_slots WHERE doctor_id = :did AND slot_date >= CURDATE() AND is_booked = 0");
$stmt->execute([":did" => $doctor_id]);
$free_slots_count = $stmt->fetch()['cnt'];
?>

<?php
// Fetch today's appointments for this doctor
$today_stmt = $conn->prepare("
    SELECT a.id, a.appointment_time, a.status, a.reason, u.full_name as patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = :did AND a.appointment_date = CURDATE() AND a.status != 'cancelled'
    ORDER BY a.appointment_time ASC
");
$today_stmt->execute([':did' => $doctor_id]);
$today_appointments = $today_stmt->fetchAll();
?>

<?php require_once "../includes/header.php"; ?>

<h2 style="text-align: left; margin-bottom: 0.5rem;">Сайн байна уу, <?php echo esc($_SESSION["full_name"]); ?>!</h2>
<p class="text-muted mb-4">Эмчийн самбар</p>

<div style="margin-bottom: 2rem; background: #fff; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
    <h3 style="margin-top: 0; margin-bottom: 1rem; color: #1e293b;">📅 Өнөөдрийн цагууд</h3>
    <?php if (empty($today_appointments)): ?>
        <p class="text-muted" style="margin: 0;">Өнөөдөр цаг захиалга байхгүй байна.</p>
    <?php else: ?>
        <div class="table-responsive" style="border: none; box-shadow: none;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Цаг</th>
                        <th>Өвчтөн</th>
                        <th>Шалтгаан</th>
                        <th>Төлөв</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_appointments as $app): ?>
                    <tr>
                        <td><strong><?php echo esc(substr($app['appointment_time'], 0, 5)); ?></strong></td>
                        <td><?php echo esc($app['patient_name']); ?></td>
                        <td><?php echo esc($app['reason'] ?: '-'); ?></td>
                        <td><?php echo render_status_badge($app['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div style="margin-bottom: 2rem; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
    <h3>Үзүүлэлтүүд (Statistics)</h3>
    <table class="table" style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #ddd; background: #f8fafc;">
                <th style="padding: 10px;">Дэлгэрэнгүй</th>
                <th style="padding: 10px;">Тоо хэмжээ</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">Өнөөдрийн цагууд</td>
                <td style="padding: 10px; font-weight: bold;"><?php echo $today_count; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">Хүлээгдэж буй</td>
                <td style="padding: 10px; font-weight: bold;"><?php echo $pending_count; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">Баталгаажсан</td>
                <td style="padding: 10px; font-weight: bold;"><?php echo $approved_count; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">Дууссан үзлэг</td>
                <td style="padding: 10px; font-weight: bold;"><?php echo $completed_count; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">Нийт өвчтөн</td>
                <td style="padding: 10px; font-weight: bold;"><?php echo $patient_count; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">Сул цагууд</td>
                <td style="padding: 10px; font-weight: bold;"><?php echo $free_slots_count; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="dashboard-grid">
    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='my_appointments.php'">
        <h3>Цагуудыг удирдах</h3>
        <p class="value">📋</p>
    </div>
    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='schedule.php'">
        <h3>Хуваарь үүсгэх</h3>
        <p class="value">🗓</p>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>