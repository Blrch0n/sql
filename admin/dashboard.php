<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'patient'")->fetch()['cnt'];
$total_doctors = $conn->query("SELECT COUNT(*) as cnt FROM doctors")->fetch()['cnt'];
$total_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments")->fetch()['cnt'];
$pending_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'pending'")->fetch()['cnt'];
$approved_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'approved'")->fetch()['cnt'];
$completed_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'completed'")->fetch()['cnt'];
$today_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE appointment_date = CURDATE() AND status != 'cancelled'")->fetch()['cnt'];
$total_departments = $conn->query("SELECT COUNT(*) as cnt FROM departments")->fetch()['cnt'];
?>

<?php require_once "../includes/header.php"; ?>

<h2 style="text-align: left; margin-bottom: 0.5rem;">Сайн байна уу, <?php echo esc($_SESSION["full_name"]); ?>!</h2>
<p class="text-muted mb-4">Админы самбар</p>

<div class="dashboard-grid">
    <div class="stat-card">
        <h3>Нийт өвчтөн</h3>
        <p class="value"><?php echo $total_users; ?></p>
    </div>
    <div class="stat-card">
        <h3>Нийт эмч</h3>
        <p class="value"><?php echo $total_doctors; ?></p>
    </div>
    <div class="stat-card">
        <h3>Нийт захиалга</h3>
        <p class="value"><?php echo $total_appointments; ?></p>
    </div>
    <div class="stat-card">
        <h3>Хүлээгдэж буй</h3>
        <p class="value"><?php echo $pending_appointments; ?></p>
    </div>
    <div class="stat-card">
        <h3>Баталгаажсан</h3>
        <p class="value"><?php echo $approved_appointments; ?></p>
    </div>
    <div class="stat-card">
        <h3>Дууссан</h3>
        <p class="value"><?php echo $completed_appointments; ?></p>
    </div>
    <div class="stat-card">
        <h3>Өнөөдрийн цагууд</h3>
        <p class="value"><?php echo $today_appointments; ?></p>
    </div>
    <div class="stat-card">
        <h3>Нийт тасаг</h3>
        <p class="value"><?php echo $total_departments; ?></p>
    </div>
    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='add_department.php'">
        <h3>Тасаг нэмэх</h3>
        <p class="value">🏥</p>
    </div>
    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='add_doctor.php'">
        <h3>Эмч нэмэх</h3>
        <p class="value">+</p>
    </div>
    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='manage_users.php'">
        <h3>Хэрэглэгчид</h3>
        <p class="value">👥</p>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>