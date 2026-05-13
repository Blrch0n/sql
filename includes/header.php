<?php
$is_sub_dir = (basename(dirname($_SERVER['PHP_SELF'])) == 'admin' 
            || basename(dirname($_SERVER['PHP_SELF'])) == 'doctor' 
            || basename(dirname($_SERVER['PHP_SELF'])) == 'patient');
$base_path = $is_sub_dir ? '../' : '';

require_once __DIR__ . '/ui.php';
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare System</title>
    <?php
    $css_file = __DIR__ . "/../assets/css/style.css";
    $css_version = file_exists($css_file) ? filemtime($css_file) : time();
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css?v=<?php echo $css_version; ?>">
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?php echo $base_path; ?>index.php">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            MediCare <span>System</span>
        </a>
    </div>
    <ul class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <li><a href="<?php echo $base_path; ?>admin/dashboard.php" class="<?php echo active_nav('dashboard.php'); ?>">Самбар</a></li>
                <li><a href="<?php echo $base_path; ?>admin/doctors.php" class="<?php echo active_nav('doctors.php'); ?>">Эмч нар</a></li>
                <li><a href="<?php echo $base_path; ?>admin/appointments.php" class="<?php echo active_nav('appointments.php'); ?>">Цагууд</a></li>
                <li><a href="<?php echo $base_path; ?>admin/add_department.php" class="<?php echo active_nav('add_department.php'); ?>">Тасгууд</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage_users.php" class="<?php echo active_nav('manage_users.php'); ?>">Хэрэглэгчид</a></li>
            <?php elseif ($_SESSION['role'] == 'doctor'): ?>
                <li><a href="<?php echo $base_path; ?>doctor/dashboard.php" class="<?php echo active_nav('dashboard.php'); ?>">Самбар</a></li>
                <li><a href="<?php echo $base_path; ?>doctor/my_appointments.php" class="<?php echo active_nav('my_appointments.php'); ?>">Миний цагууд</a></li>
                <li><a href="<?php echo $base_path; ?>doctor/schedule.php" class="<?php echo active_nav('schedule.php'); ?>">Хуваарь</a></li>
            <?php elseif ($_SESSION['role'] == 'patient'): ?>
                <li><a href="<?php echo $base_path; ?>patient/dashboard.php" class="<?php echo active_nav('dashboard.php'); ?>">Самбар</a></li>
                <li><a href="<?php echo $base_path; ?>patient/doctors.php" class="<?php echo active_nav('doctors.php'); ?>">Эмч нар</a></li>
                <li><a href="<?php echo $base_path; ?>patient/book_appointment.php" class="<?php echo active_nav('book_appointment.php'); ?>">Цаг авах</a></li>
                <li><a href="<?php echo $base_path; ?>patient/my_appointments.php" class="<?php echo active_nav('my_appointments.php'); ?>">Миний цагууд</a></li>
                <li><a href="<?php echo $base_path; ?>patient/profile.php" class="<?php echo active_nav('profile.php'); ?>">Миний мэдээлэл</a></li>
            <?php endif; ?>
            
            <li class="logout-item">
                <form method="POST" action="<?php echo $base_path; ?>logout.php" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                    <button type="submit" class="btn-logout">Гарах (<?php echo esc($_SESSION['full_name']); ?>)</button>
                </form>
            </li>
        <?php else: ?>
            <li><a href="<?php echo $base_path; ?>login.php">Нэвтрэх</a></li>
            <li><a href="<?php echo $base_path; ?>register.php">Бүртгүүлэх</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="container">
