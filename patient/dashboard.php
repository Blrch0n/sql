<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];

// Analytics
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM appointments 
    WHERE patient_id = :pid
");
$stmt_stats->execute([":pid" => $patient_id]);
$stats = $stmt_stats->fetch();

// Next upcoming appointment
$stmt_next = $conn->prepare("
    SELECT a.*, d.specialization, u.full_name as doctor_name, dep.name as department_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN departments dep ON d.department_id = dep.id
    WHERE a.patient_id = :pid 
      AND a.appointment_date >= CURRENT_DATE 
      AND a.status IN ('pending', 'approved')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 1
");
$stmt_next->execute([":pid" => $patient_id]);
$next_appointment = $stmt_next->fetch();

require_once "../includes/header.php";
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; margin-top: 1rem;">
    <div>
        <h2 style="margin-bottom: 0.5rem; color: #1e293b;">Сайн байна уу, <?php echo esc($_SESSION["full_name"]); ?> 👋</h2>
        <p class="text-muted" style="margin: 0;">Өвчтөний удирдлагын самбарт тавтай морилно уу.</p>
    </div>
</div>

<div class="row" style="margin-bottom: 2rem;">
    <?php if ($next_appointment): ?>
    <div class="col-md-12" style="margin-bottom: 1.5rem;">
        <div class="card" style="background: linear-gradient(135deg, #0ea5e9, #38bdf8); color: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(14, 165, 233, 0.2);">
            <h4 style="margin-top: 0; color: rgba(255,255,255,0.9); font-weight: normal; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Таны дараагийн цаг</h4>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 1rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div style="font-size: 1.8rem; font-weight: bold; margin-bottom: 5px;">
                        📅 <?php echo date('Y-m-d', strtotime($next_appointment['appointment_date'])); ?> 
                        🕒 <?php echo date('H:i', strtotime($next_appointment['appointment_time'])); ?>
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">
                        Эмч: Др. <?php echo esc($next_appointment['doctor_name']); ?> (<?php echo esc($next_appointment['department_name']); ?>)
                    </div>
                </div>
                <div>
                    <?php echo render_status_badge($next_appointment['status']); ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-md-12" style="margin-bottom: 1.5rem;">
        <div class="card" style="background: #f8fafc; padding: 2rem; border-radius: 12px; border: 1px dashed #cbd5e1; text-align: center;">
            <p style="color: #64748b; font-size: 1.1rem; margin-bottom: 15px;">Танд одоогоор захиалсан цаг алга байна.</p>
            <a href="book_appointment.php" class="btn btn-primary">Эмчид цаг авах</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- User stats -->
    <div class="col-md-3">
        <div class="stat-card" style="text-align: center; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1rem;">
            <div style="font-size: 2.5rem; color: #0284c7; font-weight: bold;"><?php echo (int)$stats['total']; ?></div>
            <div style="color: #64748b; font-weight: 500; text-transform: uppercase; font-size: 0.85rem; margin-top: 5px;">Нийт цаг</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="text-align: center; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1rem; border-bottom: 4px solid #f59e0b;">
            <div style="font-size: 2.5rem; color: #1e293b; font-weight: bold;"><?php echo (int)$stats['pending']; ?></div>
            <div style="color: #64748b; font-weight: 500; text-transform: uppercase; font-size: 0.85rem; margin-top: 5px;">Хүлээгдэж буй</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="text-align: center; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1rem; border-bottom: 4px solid #10b981;">
            <div style="font-size: 2.5rem; color: #1e293b; font-weight: bold;"><?php echo (int)$stats['approved']; ?></div>
            <div style="color: #64748b; font-weight: 500; text-transform: uppercase; font-size: 0.85rem; margin-top: 5px;">Баталгаажсан</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="text-align: center; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1rem; border-bottom: 4px solid #0ea5e9;">
            <div style="font-size: 2.5rem; color: #1e293b; font-weight: bold;"><?php echo (int)$stats['completed']; ?></div>
            <div style="color: #64748b; font-weight: 500; text-transform: uppercase; font-size: 0.85rem; margin-top: 5px;">Дууссан</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card" style="padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); background: white;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Шуурхай үйлдэл</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="book_appointment.php" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: #f8fafc; border-radius: 8px; text-decoration: none; color: #0284c7; transition: all 0.2s; border: 1px solid #e2e8f0;" onmouseover="this.style.background='#e0f2fe'; this.style.borderColor='#bae6fd'" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'">
                    <span style="font-size: 2rem; margin-bottom: 10px;">📅</span>
                    <span style="font-weight: bold;">Цаг авах</span>
                </a>
                
                <a href="doctors.php" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: #f8fafc; border-radius: 8px; text-decoration: none; color: #0284c7; transition: all 0.2s; border: 1px solid #e2e8f0;" onmouseover="this.style.background='#e0f2fe'; this.style.borderColor='#bae6fd'" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'">
                    <span style="font-size: 2rem; margin-bottom: 10px;">👨‍⚕️</span>
                    <span style="font-weight: bold;">Эмч хайх</span>
                </a>
                
                <a href="my_appointments.php" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: #f8fafc; border-radius: 8px; text-decoration: none; color: #0284c7; transition: all 0.2s; border: 1px solid #e2e8f0;" onmouseover="this.style.background='#e0f2fe'; this.style.borderColor='#bae6fd'" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'">
                    <span style="font-size: 2rem; margin-bottom: 10px;">📋</span>
                    <span style="font-weight: bold;">Миний цагууд</span>
                </a>

                <a href="profile.php" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: #f8fafc; border-radius: 8px; text-decoration: none; color: #0284c7; transition: all 0.2s; border: 1px solid #e2e8f0;" onmouseover="this.style.background='#e0f2fe'; this.style.borderColor='#bae6fd'" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'">
                    <span style="font-size: 2rem; margin-bottom: 10px;">👤</span>
                    <span style="font-weight: bold;">Миний мэдээлэл</span>
                </a>
            </div>
            
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
