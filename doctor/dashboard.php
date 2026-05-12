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
        ✅ <strong>SQL Injection хамгаалалт ажиллаж байна:</strong> 
        Та <code>?category=<?php echo esc($selected_category); ?></code> шүүлтүүрийг сонгосон байна.
        (PDO Prepared Statement ашиглагдсан)
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