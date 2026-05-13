<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

require_once "../includes/header.php";
?>

<div class="card" style="padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 2rem;">
    <h2>Аюулгүй байдлын туршилт (SQL Injection Demo)</h2>
    <p class="text-muted">Энэхүү хэсэг нь зөвхөн сургалтын зориулалттай бөгөөд систем хэрхэн SQL Injection-оос хамгаалагдсаныг харуулж байна.</p>

    <?php
    try {
        $dashboard_categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    } catch (Exception $e) { 
        $dashboard_categories = []; 
    }

    $selected_category = $_GET['category'] ?? '';
    $safe_category = sanitize_string($selected_category);
    ?>

    <div style="margin-bottom: 2rem; padding: 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
        <strong>Шүүлтүүр:</strong> 
        <a href="security_demo.php" style="text-decoration: none; color: <?php echo empty($safe_category) ? '#000' : '#0284c7'; ?>; font-weight: <?php echo empty($safe_category) ? 'bold' : 'normal'; ?>; padding: 0 10px;">Бүгд</a>
        
        <?php foreach ($dashboard_categories as $cat): ?>
            | <a href="security_demo.php?category=<?php echo urlencode($cat['name']); ?>" style="text-decoration: none; color: <?php echo ($safe_category === $cat['name']) ? '#000' : '#0284c7'; ?>; font-weight: <?php echo ($safe_category === $cat['name']) ? 'bold' : 'normal'; ?>; padding: 0 10px;">
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
        <div class="alert alert-info" style="margin-bottom: 2rem; background-color: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 5px;">
            ✅ <strong>SQL Injection хамгаалалт ажиллаж байна:</strong><br> 
            Та <code>?category=<?php echo esc($selected_category); ?></code> шүүлтүүрийг сонгосон байна.<br>
            <em>(PDO Prepared Statement ашиглан <code>WHERE category = :cat</code> гэж хамгаалсан тул query manipulate хийх боломжгүй.)</em>
        </div>
    <?php endif; ?>

    <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
        <h3>Бүтээгдэхүүнүүд</h3>
        <?php if (empty($ui_products)): ?>
            <?php echo render_empty_state('Бүтээгдэхүүн олдсонгүй', 'Энэ ангилалд бүтээгдэхүүн олдсонгүй.'); ?>
        <?php else: ?>
            <ul style="list-style-type: none; padding: 0; margin: 0;">
            <?php foreach ($ui_products as $prod): ?>
                <li style="padding: 15px; border-bottom: 1px solid #eee;">
                    <div style="font-weight: bold; font-size: 1.1em; color: #0f172a;"><?php echo esc($prod['name']); ?></div>
                    <div style="color: #64748b; font-size:0.9em; margin-top: 5px;">Ангилал: <?php echo esc($prod['category']); ?> | Үнэ: $<?php echo number_format($prod['price'], 2); ?></div>
                    <div style="margin-top: 10px; color: #475569;"><em><?php echo esc($prod['description']); ?></em></div>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>