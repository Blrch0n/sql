<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$raw_name_from_url = $_GET['name'] ?? '';
$category_name = sanitize_string($raw_name_from_url);

$category = null;

if (!empty($category_name)) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE name = :name LIMIT 1");
    $stmt->execute([':name' => $category_name]);
    $category = $stmt->fetch();
}
?>

<?php require_once "../includes/header.php"; ?>

<div class="patient-container">
    <h2>Ангиллын дэлгэрэнгүй</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="dashboard.php" class="btn btn-secondary">Буцах</a>
    </div>

    <?php if ($category): ?>
        <div class="card" style="padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
            <h3>Ангилал: <?php echo esc($category['name']); ?></h3>
            <p><strong>Нэмэгдсэн огноо:</strong> <?php echo esc($category['created_at']); ?></p>
            <p><strong>Таних тэмдэг (ID):</strong> <?php echo esc($category['id']); ?></p>
            
            <hr style="margin: 20px 0;">
            <p style="color: green; font-size: 0.9em;">
                <b>SQL Injection хамгаалагдсан:</b> Таны хайсан нэрийг PDO Prepared Statement ашиглан баазуу хувиргаж хайлт хийсэн.
            </p>
        </div>
    <?php else: ?>
        <div class="alert alert-error">
            <p>Ангилал олдсонгүй.</p>
            <?php if (!empty($raw_name_from_url)): ?>
                <hr>
                <p><b>Таны хайсан хувьсагч:</b> <code><?php echo esc($raw_name_from_url); ?></code></p>
                <p style="color: green; font-size: 0.9em;">🛡️ Энэ нь SQL Injection халдлагаас бүрэн хамгаалагдсан тул команд хэрэгжихгүй!</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>