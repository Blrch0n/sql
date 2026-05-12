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
try {
    $total_categories = $conn->query("SELECT COUNT(*) as cnt FROM categories")->fetch()['cnt'];
} catch (Exception $e) { $total_categories = 0; }
?>

<?php require_once "../includes/header.php"; ?>

<div class="dashboard-header">
    <h1>Сайн байна уу, <?php echo esc($_SESSION["full_name"]); ?>!</h1>
    <p>Эмнэлгийн системийн ерөнхий хяналтын самбар</p>
</div>

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
</div>

<section class="quick-actions">
    <h2>Шуурхай үйлдлүүд</h2>
    <div class="action-grid">
        <a class="action-card" href="add_department.php">
            <span class="icon">🏥</span>
            <span class="title">Тасаг нэмэх</span>
        </a>
        <a class="action-card" href="add_doctor.php">
            <span class="icon">➕</span>
            <span class="title">Эмч нэмэх</span>
        </a>
        <a class="action-card" href="add_category.php">
            <span class="icon">📂</span>
            <span class="title">Ангилал нэмэх</span>
        </a>
        <a class="action-card" href="manage_users.php">
            <span class="icon">👥</span>
            <span class="title">Хэрэглэгчид</span>
        </a>
    </div>
</section>

<?php
try {
    $dashboard_categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
} catch (Exception $e) { 
    $dashboard_categories = []; 
}

$selected_category = $_GET['category'] ?? '';
$safe_category = sanitize_string($selected_category);
?>
<div style="margin-bottom: 2rem; padding: 10px; background: #f8fafc; border-radius: 8px;">
    <strong>Шүүлтүүр (Category filter):</strong> 
    <a href="dashboard.php" style="text-decoration: none; color: <?php echo empty($safe_category) ? '#000' : '#0284c7'; ?>; font-weight: <?php echo empty($safe_category) ? 'bold' : 'normal'; ?>;">Бүгд (All)</a>
    
    <?php foreach ($dashboard_categories as $cat): ?>
        | <a href="dashboard.php?category=<?php echo urlencode($cat['name']); ?>" style="text-decoration: none; color: <?php echo ($safe_category === $cat['name']) ? '#000' : '#0284c7'; ?>; font-weight: <?php echo ($safe_category === $cat['name']) ? 'bold' : 'normal'; ?>;">
            <?php echo esc($cat['name']); ?>
        </a>
    <?php endforeach; ?>
    
    <a href="add_category.php" class="btn btn-primary" style="margin-left: 15px; padding: 5px 10px; font-size: 0.8em;">+ Шинэ ангилал нэмэх</a>
</div>

<?php 
if (!empty($safe_category)) {
    $stmt = $conn->prepare("SELECT id, name, description, price, category FROM products WHERE category = :cat AND released = 1");
    $stmt->execute([':cat' => $safe_category]);
} else {
    $stmt = $conn->prepare("SELECT id, name, description, price, category FROM products WHERE released = 1");
    $stmt->execute();
}
$ui_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (!empty($safe_category)): ?>
    <?php
        $stmt = $conn->prepare("SELECT * FROM categories WHERE name = :cat LIMIT 1");
        $stmt->execute([':cat' => $safe_category]);
        $matched_cat = $stmt->fetch();
    ?>
    <div class="alert alert-info" style="margin-bottom: 2rem; background-color: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 5px;">
        <strong>SQL Injection хамгаалалт ажиллаж байна:</strong> 
        Та <code>?category=<?php echo esc($selected_category); ?></code> шүүлтүүрийг сонгосон байна.
        Энэхүү утгыг <b>PDO Prepared Statement</b> (<code>:cat</code>) ашиглан бааз руу дамжуулсан тул хакердах боломжгүй (SQLi prevented).
    </div>
<?php endif; ?>

<div style="margin-bottom: 2rem; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
    <h3>Бүтээгдэхүүнүүд (Products in Category)</h3>
    <?php if (empty($ui_products)): ?>
        <p>Энэ ангилалд бүтээгдэхүүн байхгүй байна.</p>
    <?php else: ?>
        <ul style="list-style-type: none; padding: 0;">
        <?php foreach ($ui_products as $prod): ?>
            <li style="padding: 10px; border-bottom: 1px solid #eee;">
                <strong><?php echo esc($prod['name']); ?></strong> 
                <span style="color: #666; font-size:0.9em;">(<?php echo esc($prod['category']); ?>)</span> - 
                $<?php echo number_format($prod['price'], 2); ?><br>
                <em><?php echo esc($prod['description']); ?></em>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>