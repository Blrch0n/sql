<?php
require_once "config/security.php";
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$base_query = "SELECT * FROM products WHERE released=1";
$params = [];

if ($search) {
    $base_query .= " AND name LIKE :search";
    $params[':search'] = "%$search%";
}
$stmt = $conn->prepare($base_query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<?php require_once "includes/header.php"; ?>

<div class="glass-card page-card" style="margin-top: 2rem;">
    <h2 class="section-title">Бүтээгдэхүүн хайх</h2>
    <div style="margin-bottom: 20px;">
        <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="search" class="form-control" placeholder="Бүтээгдэхүүний нэр..." value="<?php echo esc($search); ?>" style="flex: 1; min-width: 200px;">
            <button type="submit" class="btn btn-primary">Хайх</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ангилал</th>
                    <th>Нэр</th>
                    <th>Тайлбар</th>
                    <th>Үнэ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $row): ?>
                        <tr>
                            <td><?php echo esc($row['id']); ?></td>
                            <td><?php echo esc($row['category']); ?></td>
                            <td><?php echo esc($row['name']); ?></td>
                            <td><?php echo esc($row['description']); ?></td>
                            <td><?php echo esc($row['price']); ?>$</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Бүтээгдэхүүн олдсонгүй.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
