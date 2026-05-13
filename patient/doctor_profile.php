<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($doctor_id <= 0) {
    die("Алдаатай хандалт.");
}

// Fetch doctor details
$stmt = $conn->prepare("
    SELECT d.id, d.specialization, d.phone, u.full_name, u.email, dep.name as department_name, dep.id as dep_id 
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN departments dep ON d.department_id = dep.id
    WHERE d.id = :id AND u.is_active = 1
");
$stmt->execute([':id' => $doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Эмч олдсонгүй эсвэл идэвхгүй байна.");
}

// Fetch doctor's average rating (stub for now, will work with doctor_reviews table)
$rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as review_count FROM doctor_reviews WHERE doctor_id = :Id");
$rating_stmt->execute([':Id' => $doctor_id]);
$rating_data = $rating_stmt->fetch();
$avg_rating = $rating_data['avg_rating'] ? number_format($rating_data['avg_rating'], 1) : null;
$review_count = $rating_data['review_count'];

// Fetch recent reviews for this doctor
$reviews_stmt = $conn->prepare("
    SELECT dr.rating, dr.comment, dr.created_at, u.full_name as patient_name
    FROM doctor_reviews dr
    JOIN users u ON dr.patient_id = u.id
    WHERE dr.doctor_id = :id
    ORDER BY dr.created_at DESC
    LIMIT 5
");
$reviews_stmt->execute([':id' => $doctor_id]);
$reviews = $reviews_stmt->fetchAll();

// Fetch available slots from today onwards
$slots_stmt = $conn->prepare("
    SELECT slot_date, slot_time, id
    FROM doctor_slots 
    WHERE doctor_id = :id 
      AND slot_date >= CURRENT_DATE 
      AND is_booked = 0 
    ORDER BY slot_date ASC, slot_time ASC
");
$slots_stmt->execute([':id' => $doctor_id]);
$raw_slots = $slots_stmt->fetchAll();

// Group slots by date
$slots_by_date = [];
foreach ($raw_slots as $slot) {
    // Skip today's past slots
    if ($slot['slot_date'] == date('Y-m-d') && $slot['slot_time'] <= date('H:i:s')) {
        continue;
    }
    $slots_by_date[$slot['slot_date']][] = $slot;
}

require_once "../includes/header.php";
?>

<div class="row" style="margin-top: 2rem;">
    <!-- Profile Card -->
    <div class="col-md-4">
        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 2rem; text-align: center; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 15px auto;">
                👨‍⚕️
            </div>
            <h3 style="margin: 0 0 5px 0; color: #0f172a;">Др. <?php echo esc($doctor['full_name']); ?></h3>
            <p style="color: #0284c7; font-weight: bold; margin-bottom: 20px;"><?php echo esc($doctor['department_name']); ?></p>
            
            <?php if ($avg_rating): ?>
            <div style="margin-bottom: 20px; color: #f59e0b; font-size: 1.2rem;">
                ★ <?php echo $avg_rating; ?> <span style="font-size: 0.9rem; color: #64748b;">(<?php echo $review_count; ?> үнэлгээ)</span>
            </div>
            <?php else: ?>
            <div style="margin-bottom: 20px; color: #94a3b8; font-size: 0.9rem;">
                Одоогоор үнэлгээ байхгүй байна
            </div>
            <?php endif; ?>

            <div style="text-align: left; background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="margin-bottom: 10px;">
                    <strong>Цахим шуудан:</strong><br>
                    <span style="color: #475569;"><?php echo esc($doctor['email']); ?></span>
                </div>
                <div style="margin-bottom: 10px;">
                    <strong>Утасны дугаар:</strong><br>
                    <span style="color: #475569;"><?php echo esc($doctor['phone'] ?: '-'); ?></span>
                </div>
                <div>
                    <strong>Нарийн мэргэжил:</strong><br>
                    <span style="color: #475569;"><?php echo esc($doctor['specialization'] ?: '-'); ?></span>
                </div>
            </div>

            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.1rem;">Цаг авах</a>
        </div>
    </div>

    <!-- Available Slots -->
    <div class="col-md-8">
        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 2rem; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Боломжтой цагууд (Дараагийн өдрүүдэд)</h3>

            <?php if (empty($slots_by_date)): ?>
                <?php echo render_empty_state('Цаг олдсонгүй', 'Энэ эмчид ойрын үед боломжтой цаг алга байна.'); ?>
            <?php else: ?>
                <?php foreach ($slots_by_date as $date => $slots): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h5 style="color: #334155; margin-bottom: 10px;">
                            📅 <?php echo date('Y-m-d, l', strtotime($date)); ?>
                        </h5>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php foreach ($slots as $slot): ?>
                                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>&date=<?php echo $date; ?>&slot_id=<?php echo $slot['id']; ?>" 
                                   style="display: inline-block; padding: 8px 15px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 20px; color: #0284c7; text-decoration: none; font-weight: 500; transition: all 0.2s;"
                                   onmouseover="this.style.background='#e0f2fe'; this.style.borderColor='#7dd3fc';"
                                   onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';">
                                    <?php echo date('H:i', strtotime($slot['slot_time'])); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Doctor Reviews Section -->
        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 2rem; border: 1px solid #e2e8f0; margin-top: 1.5rem;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                ★ Өвчтөнүүдийн үнэлгээ
            </h3>
            <?php if (empty($reviews)): ?>
                <p style="color: #94a3b8; margin: 0;">Одоогоор үнэлгээ байхгүй байна.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <div style="border-bottom: 1px solid #f1f5f9; padding: 15px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="color: #f59e0b; font-size: 1.1rem;">
                            <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                        </span>
                        <span style="font-size: 0.82rem; color: #94a3b8;"><?php echo esc(date('Y-m-d', strtotime($review['created_at']))); ?></span>
                    </div>
                    <?php if (!empty($review['comment'])): ?>
                    <p style="margin: 0 0 4px 0; color: #334155; font-size: 0.95rem;"><?php echo nl2br(esc($review['comment'])); ?></p>
                    <?php endif; ?>
                    <small style="color: #94a3b8;">— <?php echo esc($review['patient_name']); ?></small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>