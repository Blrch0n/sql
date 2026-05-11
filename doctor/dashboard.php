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

<?php require_once "../includes/header.php"; ?>

<h2 style="text-align: left; margin-bottom: 0.5rem;">Сайн байна уу, <?php echo esc($_SESSION["full_name"]); ?>!</h2>
<p class="text-muted mb-4">Эмчийн самбар</p>

<div class="dashboard-grid">
    <div class="stat-card">
        <h3>Өнөөдрийн цагууд</h3>
        <p class="value"><?php echo $today_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Хүлээгдэж буй</h3>
        <p class="value"><?php echo $pending_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Баталгаажсан</h3>
        <p class="value"><?php echo $approved_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Дууссан үзлэг</h3>
        <p class="value"><?php echo $completed_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Нийт өвчтөн</h3>
        <p class="value"><?php echo $patient_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Сул цагууд</h3>
        <p class="value"><?php echo $free_slots_count; ?></p>
    </div>
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