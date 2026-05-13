<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];
$filter_error = '';

// Filters
$status = isset($_GET['status']) ? sanitize_string($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_string($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_string($_GET['date_to']) : '';
$doctor_search = isset($_GET['doctor_search']) ? sanitize_string($_GET['doctor_search']) : '';
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

// Validate date range
if (!empty($date_from) && !empty($date_to) && $date_from > $date_to) {
    $filter_error = "Огноо шүүлтүүрийн 'аас' огноо 'хүртэл' огнооноос өмнө байх ёстой.";
}

$appointments = [];

if (empty($filter_error)) {
    $query = "
        SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason,
               d.specialization, u.full_name as doctor_name, dep.name as department_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN departments dep ON d.department_id = dep.id
        WHERE a.patient_id = :pid
    ";
    $params = [':pid' => $patient_id];

    if (!empty($status)) {
        $query .= " AND a.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($date_from)) {
        $query .= " AND a.appointment_date >= :date_from";
        $params[':date_from'] = $date_from;
    }

    if (!empty($date_to)) {
        $query .= " AND a.appointment_date <= :date_to";
        $params[':date_to'] = $date_to;
    }

    if (!empty($doctor_search)) {
        $query .= " AND u.full_name LIKE :doc_search";
        $params[':doc_search'] = '%' . $doctor_search . '%';
    }

    if ($department_id > 0) {
        $query .= " AND d.department_id = :dep_id";
        $params[':dep_id'] = $department_id;
    }

    $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch departments for filter
$deps = $conn->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

require_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; margin-bottom: 2rem;">
            <h2 style="margin: 0; color: #1e293b;">Миний цагууд</h2>
            <a href="book_appointment.php" class="btn btn-primary">Шинэ цаг авах</a>
        </div>

        <?php
            if (isset($_SESSION['success_msg'])) {
                echo render_alert($_SESSION['success_msg'], 'success');
                unset($_SESSION['success_msg']);
            }
            if (isset($_SESSION['error_msg'])) {
                echo render_alert($_SESSION['error_msg'], 'danger');
                unset($_SESSION['error_msg']);
            }
            if (!empty($filter_error)) {
                echo render_alert($filter_error, 'danger');
            }
        ?>

        <!-- Filter Card -->
        <div class="card" style="padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); background: white; margin-bottom: 2rem;">
            <form method="GET" action="my_appointments.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block; color: #475569;">Төлөв</label>
                    <select name="status" class="form-control">
                        <option value="">Бүх төлөв</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Хүлээгдэж буй</option>
                        <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Баталгаажсан</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Дууссан</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Цуцлагдсан</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block; color: #475569;">Тасаг</label>
                    <select name="department_id" class="form-control">
                        <option value="0">Бүх тасаг</option>
                        <?php foreach ($deps as $dep): ?>
                            <option value="<?php echo $dep['id']; ?>" <?php echo $department_id == $dep['id'] ? 'selected' : ''; ?>>
                                <?php echo esc($dep['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block; color: #475569;">Огноо (аас)</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo esc($date_from); ?>">
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block; color: #475569;">Огноо (хүртэл)</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo esc($date_to); ?>">
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block; color: #475569;">Эмчээр хайх</label>
                    <input type="text" name="doctor_search" class="form-control" placeholder="Эмчийн нэр..." value="<?php echo esc($doctor_search); ?>">
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Шүүх</button>
                    <?php if (!empty($status) || !empty($date_from) || !empty($date_to) || !empty($doctor_search) || $department_id > 0): ?>
                        <a href="my_appointments.php" class="btn btn-secondary" style="padding: 10px 20px;">Цэвэрлэх</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
            <?php if (empty($appointments)): ?>
                <div style="padding: 2rem;">
                    <?php echo render_empty_state('Цаг олдсонгүй', 'Таны шүүлтүүрт тохирох эмнэлэгт үзүүлэх захиалга олдсонгүй.'); ?>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                                <th style="padding: 15px; color: #475569;">Огноо & Цаг</th>
                                <th style="padding: 15px; color: #475569;">Эмч</th>
                                <th style="padding: 15px; color: #475569;">Тасаг</th>
                                <th style="padding: 15px; color: #475569;">Төлөв</th>
                                <th style="padding: 15px; color: #475569; text-align: center;">Үйлдэл</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 15px;">
                                    <div style="font-weight: bold; color: #0f172a;"><?php echo date('Y-m-d', strtotime($appt['appointment_date'])); ?></div>
                                    <div style="color: #64748b; font-size: 0.9em;"><?php echo date('H:i', strtotime($appt['appointment_time'])); ?></div>
                                </td>
                                <td style="padding: 15px;">
                                    <div style="font-weight: 500;">Др. <?php echo esc($appt['doctor_name']); ?></div>
                                    <div style="color: #64748b; font-size: 0.85em;"><?php echo esc($appt['specialization']); ?></div>
                                </td>
                                <td style="padding: 15px;">
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 0.85em;">
                                        <?php echo esc($appt['department_name']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px;">
                                    <?php echo render_status_badge($appt['status']); ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <a href="appointment_detail.php?id=<?php echo $appt['id']; ?>" class="btn btn-secondary btn-sm" style="font-size: 0.85rem; padding: 5px 10px;">Дэлгэрэнгүй</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
