<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

// Filters
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$search = isset($_GET['search']) ? sanitize_string($_GET['search']) : '';

// Fetch all departments for filter dropdown
$deps = $conn->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

// Build query for doctors
$query = "SELECT d.id, u.full_name, u.email, d.specialization, d.phone, dep.name as department_name, dep.id as dep_id
          FROM doctors d
          JOIN users u ON d.user_id = u.id
          JOIN departments dep ON d.department_id = dep.id
          WHERE u.is_active = 1";
          
$params = [];

if ($department_id > 0) {
    $query .= " AND d.department_id = :dep_id";
    $params[':dep_id'] = $department_id;
}

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE :search OR d.specialization LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY u.full_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2 style="margin-top: 1rem; margin-bottom: 2rem; color: #1e293b;">Эмч нар</h2>

        <!-- Filters -->
        <div class="card" style="padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); background: white; margin-bottom: 2rem;">
            <form method="GET" action="doctors.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                
                <div style="flex: 1; min-width: 200px;">
                    <label for="search" style="font-weight: bold; margin-bottom: 5px; display: block; color: #475569;">Хайх</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Эмчийн нэр, мэргэжил..." value="<?php echo esc($search); ?>">
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label for="department_id" style="font-weight: bold; margin-bottom: 5px; display: block; color: #475569;">Тасаг</label>
                    <select name="department_id" id="department_id" class="form-control">
                        <option value="0">Бүх тасаг</option>
                        <?php foreach ($deps as $dep): ?>
                            <option value="<?php echo $dep['id']; ?>" <?php echo $department_id == $dep['id'] ? 'selected' : ''; ?>>
                                <?php echo esc($dep['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Шүүх</button>
                    <?php if ($department_id > 0 || !empty($search)): ?>
                        <a href="doctors.php" class="btn btn-secondary" style="padding: 10px 20px;">Цэвэрлэх</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Doctor Grid -->
        <?php if (empty($doctors)): ?>
            <?php echo render_empty_state('Илэрц олдсонгүй', 'Таны хайсан нөхцөлд тохирох эмч олдсонгүй.'); ?>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                <?php foreach ($doctors as $doc): ?>
                    <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; transition: transform 0.2s; border: 1px solid #e2e8f0;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div style="text-align: center; margin-bottom: 15px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 10px auto;">
                                👨‍⚕️
                            </div>
                            <h4 style="margin: 0; color: #0f172a; font-size: 1.2rem;">Др. <?php echo esc($doc['full_name']); ?></h4>
                            <p style="color: #64748b; font-size: 0.9rem; margin: 5px 0 0 0;"><?php echo esc($doc['department_name']); ?></p>
                        </div>
                        
                        <div style="border-top: 1px solid #f1f5f9; margin-top: 15px; padding-top: 15px; font-size: 0.9rem; color: #475569;">
                            <div style="margin-bottom: 8px;"><strong>Мэргэжил:</strong> <?php echo esc($doc['specialization'] ?: '-'); ?></div>
                            <div style="margin-bottom: 15px;"><strong>Утас:</strong> <?php echo esc($doc['phone'] ?: '-'); ?></div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: auto;">
                            <a href="doctor_profile.php?id=<?php echo $doc['id']; ?>" class="btn btn-secondary" style="flex: 1; text-align: center; padding: 8px;">Профайл</a>
                            <a href="book_appointment.php?doctor_id=<?php echo $doc['id']; ?>&department_id=<?php echo $doc['dep_id']; ?>" class="btn btn-primary" style="flex: 1; text-align: center; padding: 8px;">Цаг авах</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>