<?php
require_once "config/security.php";
require_once "config/db.php";

$category = trim($_GET['category'] ?? '');

try {
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
    $error_message = "Серверт алдаа гарлаа. Та дараа дахин оролдоно уу."; 
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products Filter</title>
</head>
<body>
    <h2>Products</h2>
    
    <div style="margin-bottom: 20px;">
        <strong>Category filter:</strong>
        <a href="products.php">All</a> | 
        <a href="products.php?category=Gifts">Gifts</a> | 
        <a href="products.php?category=Tech gifts">Tech gifts</a>
    </div>

    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo esc($error_message); ?></p>
    <?php endif; ?>

    <ul>
        <?php foreach ($products as $product): ?>
            <li>
                <strong><?php echo esc($product['name']); ?></strong> 
                (<?php echo esc($product['category']); ?>) - 
                $<?php echo number_format($product['price'], 2); ?>
            </li>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <li>No products found.</li>
        <?php endif; ?>
    </ul>
</body>
</html>