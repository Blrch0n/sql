<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

// Gather advanced statistics for the report
$total_users = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'patient' AND is_active = 1")->fetchColumn();
$total_doctors = $conn->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$total_appointments = $conn->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$completed_appointments = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn();

// Doctors load stats
$doctors_load = $conn->query("
    SELECT u.full_name, dep.name as dept_name, count(a.id) as appt_count
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN departments dep ON d.department_id = dep.id
    LEFT JOIN appointments a ON a.doctor_id = d.id AND a.status = 'completed'
    GROUP BY d.id, u.full_name, dep.name
    ORDER BY appt_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Эмнэлгийн Статистик Тайлан</title>
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 20px; background: #fff; }
        .report-header { text-align: center; border-bottom: 2px solid #1e293b; padding-bottom: 20px; margin-bottom: 30px; }
        .report-header h1 { margin: 0 0 10px 0; color: #0f172a; }
        .report-header p { margin: 0; color: #64748b; }
        .stat-grid { display: flex; gap: 20px; margin-bottom: 40px; }
        .stat-box { flex: 1; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-box h2 { margin: 0; font-size: 2rem; color: #2563eb; }
        .stat-box p { margin: 5px 0 0 0; color: #475569; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; color: #1e293b; font-weight: bold; }
        td { color: #334155; }
        .footer { text-align: center; margin-top: 50px; font-size: 0.8rem; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .print-btn { display: block; width: 200px; margin: 0 auto 30px auto; padding: 10px 20px; background: #dc2626; color: white; text-align: center; text-decoration: none; border-radius: 5px; font-weight: bold; cursor: pointer; border: none; }
        @media print {
            .print-btn { display: none !important; }
            body { padding: 0; -webkit-print-color-adjust: exact; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>

    <button id="print-btn" class="print-btn">🖨️ PDF-р хадгалах / Хэвлэх</button>

    <div class="report-header">
        <h1>Эмнэлгийн Нэгдсэн Тайлан</h1>
        <p>Тайлан үүсгэсэн огноо: <?php echo date('Y-m-d H:i'); ?></p>
        <p>Админ: <?php echo esc($_SESSION['full_name']); ?></p>
    </div>

    <div class="stat-grid">
        <div class="stat-box">
            <h2><?php echo $total_users; ?></h2>
            <p>Нийт өвчтөн</p>
        </div>
        <div class="stat-box">
            <h2><?php echo $total_doctors; ?></h2>
            <p>Нийт эмч</p>
        </div>
        <div class="stat-box">
            <h2><?php echo $total_appointments; ?></h2>
            <p>Нийт захиалга</p>
        </div>
        <div class="stat-box">
            <h2><?php echo $completed_appointments; ?></h2>
            <p>Дууссан үзлэг</p>
        </div>
    </div>

    <h3 style="color: #0f172a; margin-bottom: 15px;">Эмч нарын ачаалал (Дууссан үзлэгээр)</h3>
    <table>
        <thead>
            <tr>
                <th>Д/д</th>
                <th>Эмчийн нэр</th>
                <th>Тасаг</th>
                <th>Үзүүлсэн өвчтөний тоо</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($doctors_load as $index => $doc): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td>Др. <?php echo esc($doc['full_name']); ?></td>
                <td><?php echo esc($doc['dept_name']); ?></td>
                <td><strong><?php echo esc($doc['appt_count']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>© <?php echo date('Y'); ?> Эмнэлгийн Цаг Захиалгын Систем. Энэхүү тайланг системээс автоматаар үүсгэв.</p>
    </div>

    <script src="../assets/js/print.js"></script>
</body>
</html>
