<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');
    $category_name = sanitize_string($_POST["category_name"] ?? '');

    if (empty($category_name)) {
        $error = "Ангиллын нэрийг оруулна уу!";
    } elseif (strlen($category_name) > 100) {
        $error = "Ангиллын нэр 100 тэмдэгтээс хэтрэхгүй байх ёстой.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$category_name]);
            $success = "Ангилал амжилттай нэмэгдлээ!";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Энэ нэртэй ангилал аль хэдийн бүртгэлтэй байна.";
            } else {
                error_log("Db Error: " . $e->getMessage());
                $error = "Системийн алдаа гарлаа. Та дахин оролдоно уу.";
            }
        }
    }
}
?>

<?php require_once "../includes/header.php"; ?>

<div class="admin-form-container">
    <h2>Ангилал нэмэх</h2>

    <?php if(!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group">
            <label for="category_name">Ангиллын нэр:</label>
            <input type="text" id="category_name" name="category_name" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Нэмэх</button>
        <a href="dashboard.php" class="btn btn-secondary" style="margin-top: 15px;">Буцах</a>
    </form>
    
    <h3 style="margin-top: 30px;">Сүүлд нэмэгдсэн ангиллууд:</h3>
    <ul>
        <?php
        try {
            $cats = $conn->query("SELECT * FROM categories ORDER BY id DESC LIMIT 5");
            while ($cat = $cats->fetch()) {
                $safeUrl = "view_category.php?name=" . urlencode($cat['name']);
                echo "<li><a href='{$safeUrl}'>" . esc($cat['name']) . "</a></li>";
            }
        } catch (Exception $e) {
            echo "Хүснэгт үүсээгүй байна.";
        }
        ?>
    </ul>
</div>

<?php require_once "../includes/footer.php"; ?>