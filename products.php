<?php
require_once "config/security.php";
require_once "config/db.php";

$category = trim($_GET['category'] ?? '');

try {
    // 1. Fetch distinct active categories for the filter from the DB
    $cat_stmt = $conn->query("SELECT DISTINCT category FROM products WHERE released = 1 AND category IS NOT NULL AND category != ''");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Fetch products based on selected category
    if (!empty($category)) {
        $stmt = $conn->prepare("SELECT id, name, description, price, category FROM products WHERE category = :cat AND released = 1");
        $stmt->execute([':cat' => $category]);
    } else {
        $stmt = $conn->prepare("SELECT id, name, description, price, category FROM products WHERE released = 1");
        $stmt->execute();
    }
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in products.php: " . $e->getMessage());
    $products = [];
    $categories = [];
    $error_message = "Серверт алдаа гарлаа. Та дараа дахин оролдоно уу."; 
}

require_once "includes/header.php";
?>

<div class="container">
    <h2>Бүтээгдэхүүн</h2>
    
    <div class="filter-bar">
        <label>Ангилал:</label>
        <a href="products.php" class="<?php echo empty($category) ? 'text-primary' : 'text-muted'; ?>">Бүгд</a>
        <?php foreach ($categories as $cat): ?>
            <span class="text-muted">|</span>
            <a href="products.php?category=<?php echo urlencode($cat); ?>" class="<?php echo ($category === $cat) ? 'text-primary' : 'text-muted'; ?>">
                <?php echo esc($cat); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert-error"><?php echo esc($error_message); ?></div>
    <?php endif; ?>

    <div class="glass-card">
        <?php if (empty($products)): ?>
            <p class="text-muted">Бүтээгдэхүүн олдсонгүй.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Бүтээгдэхүүн</th>
                            <th>Ангилал</th>
                            <th>Үнэ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?php echo esc($product['name']); ?></strong></td>
                                <td><?php echo esc($product['category']); ?></td>
                                <td>₮<?php echo number_format($product['price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>