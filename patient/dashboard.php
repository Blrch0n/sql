<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];

$total_appts = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = :pid");
$total_appts->execute([":pid" => $patient_id]);
$total_count = $total_appts->fetch()['cnt'];

$pending_appts = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = :pid AND status = 'pending'");
$pending_appts->execute([":pid" => $patient_id]);
$pending_count = $pending_appts->fetch()['cnt'];

$approved_appts = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = :pid AND status = 'approved'");
$approved_appts->execute([":pid" => $patient_id]);
$approved_count = $approved_appts->fetch()['cnt'];
?>

<?php require_once "../includes/header.php"; ?>

<h2 style="text-align: left; margin-bottom: 0.5rem;">Сайн байна уу, <?php echo esc($_SESSION["full_name"]); ?>!</h2>
<p class="text-muted mb-4">Өвчтөний самбар</p>

<div class="dashboard-grid">
    <div class="stat-card">
        <h3>Нийт захиалга</h3>
        <p class="value"><?php echo $total_count; ?></p>
    </div>

    <div class="stat-card">
        <h3>Хүлээгдэж буй</h3>
        <p class="value"><?php echo $pending_count; ?></p>
    </div>

    <div class="stat-card">
        <h3>Баталгаажсан</h3>
        <p class="value"><?php echo $approved_count; ?></p>
    </div>

    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='book_appointment.php'">
        <h3>Шинэ цаг авах</h3>
        <p class="value">+</p>
    </div>

    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='my_appointments.php'">
        <h3>Миний цагууд</h3>
        <p class="value">📋</p>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>