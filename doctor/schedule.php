<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('doctor');

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Эмчийн мэдээлэл олдсонгүй.");
}
$doctor_id = $doctor['id'];

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST["csrf_token"] ?? '');

    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $slot_date = $_POST["slot_date"] ?? '';
        $slot_times = $_POST["slot_times"] ?? [];

        if (empty($slot_date) || empty($slot_times) || !is_array($slot_times)) {
            $error = "Огноо болон цагийг сонгоно уу!";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $slot_date)) {
            $error = "Огнооны формат буруу байна!";
        } elseif ($slot_date < date('Y-m-d')) {
            $error = "Өнгөрсөн өдөр цагийн хуваарь нэмэх боломжгүй!";
        } else {
            try {
                $conn->beginTransaction();
                $insert_slot = $conn->prepare("INSERT IGNORE INTO doctor_slots (doctor_id, slot_date, slot_time) VALUES (?, ?, ?)");
                
                $added = 0;
                foreach ($slot_times as $time) {
                    if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                        if (strlen($time) === 5) $time .= ':00';
                        
                        if ($slot_date === date('Y-m-d') && $time <= date('H:i:s')) {
                            continue;
                        }
                        
                        $insert_slot->execute([$doctor_id, $slot_date, $time]);
                        if ($insert_slot->rowCount() > 0) $added++;
                    }
                }
                
                $conn->commit();
                if ($added > 0) {
                    $success = "{$added} цагийн хуваарь амжилттай нэмэгдлээ!";
                } else {
                    $error = "Бүх сонгосон цагууд аль хэдийн нэмэгдсэн эсвэл хүчингүй байна.";
                }
            } catch(PDOException $e) {
                $conn->rollBack();
                error_log("Schedule add error: " . $e->getMessage());
                $error = "Системийн алдаа гарлаа. Дахин оролдоно уу.";
            }
        }
    } elseif ($action === 'delete_slot') {
        $slot_id = (int)($_POST['slot_id'] ?? 0);
        
        if ($slot_id <= 0) {
            $error = "Буруу хуваарь.";
        } else {
            $slot_stmt = $conn->prepare("SELECT * FROM doctor_slots WHERE id = ? AND doctor_id = ?");
            $slot_stmt->execute([$slot_id, $doctor_id]);
            $slot = $slot_stmt->fetch();
            
            if (!$slot) {
                $error = "Хуваарь олдсонгүй.";
            } elseif ($slot['is_booked']) {
                try {
                    $conn->beginTransaction();
                    
                    $cancel_appt = $conn->prepare("
                        UPDATE appointments SET status = 'cancelled' 
                        WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                        AND status IN ('pending', 'approved')
                    ");
                    $cancel_appt->execute([$doctor_id, $slot['slot_date'], $slot['slot_time']]);
                    
                    $del = $conn->prepare("DELETE FROM doctor_slots WHERE id = ? AND doctor_id = ?");
                    $del->execute([$slot_id, $doctor_id]);
                    
                    $conn->commit();
                    $success = "Хуваарь устгагдлаа. Холбоотой захиалга цуцлагдлаа.";
                } catch (PDOException $e) {
                    $conn->rollBack();
                    error_log("Slot delete error: " . $e->getMessage());
                    $error = "Системийн алдаа гарлаа.";
                }
            } else {
                $del = $conn->prepare("DELETE FROM doctor_slots WHERE id = ? AND doctor_id = ?");
                $del->execute([$slot_id, $doctor_id]);
                $success = "Хуваарь амжилттай устгагдлаа.";
            }
        }
    }
}

$slots_stmt = $conn->prepare("
    SELECT * FROM doctor_slots 
    WHERE doctor_id = ? AND slot_date >= CURRENT_DATE 
    ORDER BY slot_date ASC, slot_time ASC
");
$slots_stmt->execute([$doctor_id]);
$slots = $slots_stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_slots = [];
foreach ($slots as $slot) {
    $grouped_slots[$slot['slot_date']][] = $slot;
}
?>
<?php require_once "../includes/header.php"; ?>

<div class="glass-card mt-4" style="max-width: 800px; margin-left: auto; margin-right: auto;">
    <h2>Цагийн хуваарь гаргах</h2>
    <a href="dashboard.php" class="btn btn-secondary btn-small mb-3" style="display:inline-block; width:auto;">← Буцах</a>

    <?php if($error) echo "<div class='alert-error'>" . esc($error) . "</div>"; ?>
    <?php if($success) echo "<div class='alert-success'>" . esc($success) . "</div>"; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label>Огноо:</label>
            <input type="date" name="slot_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group">
            <label>Боломжтой цагууд (Олон цаг сонгох боломжтой):</label>
            <div class="slot-grid">
                <?php
                $times = ["09:00", "09:30", "10:00", "10:30", "11:00", "11:30", "13:00", "13:30", "14:00", "14:30", "15:00", "15:30", "16:00", "16:30"];
                foreach($times as $t) {
                    echo "<label><input type='checkbox' name='slot_times[]' value='{$t}:00'> <span>{$t}</span></label>";
                }
                ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-4">Хуваарь нэмэх</button>
    </form>
</div>

<div class="glass-card mt-4" style="max-width: 800px; margin-left: auto; margin-right: auto;">
    <h3>Таны хуваарьт цагууд</h3>
    
    <?php if (empty($grouped_slots)): ?>
        <p class="text-muted mt-4">Одоогоор цагийн хуваарь гараагүй байна.</p>
    <?php else: ?>
        <?php foreach ($grouped_slots as $date => $day_slots): ?>
            <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem; font-size: 1.1rem;">
                📅 <?php echo esc($date); ?>
                <span class="text-muted" style="font-weight: 600; font-size: 0.9rem;">
                    (<?php 
                        $day_names = ['Ням', 'Даваа', 'Мягмар', 'Лхагва', 'Пүрэв', 'Баасан', 'Бямба'];
                        echo $day_names[date('w', strtotime($date))]; 
                    ?>)
                </span>
            </h4>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Цаг</th>
                            <th>Төлөв</th>
                            <th>Үйлдэл</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($day_slots as $slot): ?>
                        <tr>
                            <td><?php echo esc(substr($slot['slot_time'], 0, 5)); ?></td>
                            <td>
                                <?php if ($slot['is_booked']): ?>
                                    <span class="badge badge-danger">Захиалагдсан</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Сул</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc(generate_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="delete_slot">
                                    <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small" 
                                        onclick="return confirm('<?php echo $slot['is_booked'] ? 'Энэ цаг захиалагдсан байна. Устгавал захиалга цуцлагдна. Итгэлтэй байна уу?' : 'Энэ цагийг устгахдаа итгэлтэй байна уу?'; ?>');">
                                        Устгах
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>