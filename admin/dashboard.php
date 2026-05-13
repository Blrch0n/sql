<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('admin');

$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'patient'")->fetch()['cnt'];
$total_doctors = $conn->query("SELECT COUNT(*) as cnt FROM doctors")->fetch()['cnt'];
$total_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments")->fetch()['cnt'];
$pending_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'pending'")->fetch()['cnt'];
$approved_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'approved'")->fetch()['cnt'];
$completed_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'completed'")->fetch()['cnt'];
$today_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE appointment_date = CURDATE() AND status != 'cancelled'")->fetch()['cnt'];
$total_departments = $conn->query("SELECT COUNT(*) as cnt FROM departments")->fetch()['cnt'];

require_once "../includes/header.php";
?>

<div class="row" style="margin-top: 2rem;">
    <div class="col-12 text-center mb-4">
        <h2 style="color: #0f172a; margin-bottom: 5px;">Админ удирдлагын самбар</h2>
        <p style="color: #64748b;">Эмнэлгийн системийн ерөнхий статистик болон тайлан</p>
    </div>

    <!-- Quick Stats -->
    <div class="col-md-3 mb-4">
        <div class="card stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="card-body text-center">
                <div style="font-size: 2.5rem; color: #3b82f6;">👥</div>
                <h4 style="margin: 10px 0; color: #1e293b;"><?php echo $total_users; ?></h4>
                <div style="color: #64748b; font-size: 0.9rem;">Нийт өвчтөн</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card stat-card" style="border-left: 4px solid #10b981;">
            <div class="card-body text-center">
                <div style="font-size: 2.5rem; color: #10b981;">👨‍⚕️</div>
                <h4 style="margin: 10px 0; color: #1e293b;"><?php echo $total_doctors; ?></h4>
                <div style="color: #64748b; font-size: 0.9rem;">Нийт эмч</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="card-body text-center">
                <div style="font-size: 2.5rem; color: #8b5cf6;">📅</div>
                <h4 style="margin: 10px 0; color: #1e293b;"><?php echo $today_appointments; ?></h4>
                <div style="color: #64748b; font-size: 0.9rem;">Өнөөдрийн цагууд</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="card-body text-center">
                <div style="font-size: 2.5rem; color: #f59e0b;">⏳</div>
                <h4 style="margin: 10px 0; color: #1e293b;"><?php echo $pending_appointments; ?></h4>
                <div style="color: #64748b; font-size: 0.9rem;">Хүлээгдэж буй</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Actions -->
    <div class="col-md-6 mb-4">
        <div class="card" style="height: 100%;">
            <div class="card-header bg-white" style="border-bottom: 1px solid #e2e8f0; padding: 15px 20px;">
                <h5 style="margin: 0; color: #0f172a;">Шуурхай үйлдлүүд</h5>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <a href="appointments.php" class="btn btn-outline-primary" style="text-align: left; padding: 15px; border-radius: 8px;">
                        <span style="font-size: 1.5rem; display: block; margin-bottom: 5px;">📋</span>
                        Захиалга удирдах
                    </a>
                    <a href="doctors.php" class="btn btn-outline-success" style="text-align: left; padding: 15px; border-radius: 8px;">
                        <span style="font-size: 1.5rem; display: block; margin-bottom: 5px;">🩺</span>
                        Эмч нар удирдах
                    </a>
                    <a href="manage_users.php" class="btn btn-outline-info" style="text-align: left; padding: 15px; border-radius: 8px;">
                        <span style="font-size: 1.5rem; display: block; margin-bottom: 5px;">👥</span>
                        Хэрэглэгчид (Өвчтөн)
                    </a>
                    <a href="add_department.php" class="btn btn-outline-secondary" style="text-align: left; padding: 15px; border-radius: 8px;">
                        <span style="font-size: 1.5rem; display: block; margin-bottom: 5px;">🏥</span>
                        Тасаг удирдах
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports -->
    <div class="col-md-6 mb-4">
        <div class="card" style="height: 100%;">
            <div class="card-header bg-white" style="border-bottom: 1px solid #e2e8f0; padding: 15px 20px;">
                <h5 style="margin: 0; color: #0f172a;">Тайлан ба Дата</h5>
            </div>
            <div class="card-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 15px;">
                    <h1 style="margin:0; color:#334155; font-size:3rem;">📊</h1>
                    <h4 style="margin:10px 0; color:#0f172a;">PDF Тайлан үүсгэх</h4>
                    <p style="color:#64748b; font-size:0.9rem;">Эмнэлгийн нэгдсэн үзүүлэлтүүд, эмч нарын ачаалал, санхүүгийн (захиалгын) тайланг PDF хувилбараар хэвлэн авах.</p>
                    <a href="report_pdf.php" target="_blank" class="btn btn-danger mt-2" style="font-weight: bold; width: 100%; border-radius: 8px; padding: 12px;">Тайлан хэвлэх (PDF)</a>
                </div>
                
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 15px;">
                    <strong style="color: #166534; display: block; margin-bottom: 5px;">Статистик:</strong>
                    <ul style="color: #15803d; margin: 0; padding-left: 20px; font-size: 0.95rem;">
                        <li>Дууссан захиалга: <b><?php echo $completed_appointments; ?></b></li>
                        <li>Нийт батлагдсан: <b><?php echo $approved_appointments; ?></b></li>
                        <li>Нийт тасаг: <b><?php echo $total_departments; ?></b></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
