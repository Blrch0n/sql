<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_auth('patient');

$patient_id = $_SESSION["user_id"];
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (isset($_POST['appointment_id'])) {
    $appointment_id = (int)$_POST['appointment_id'];
}

$error = '';
$success = '';

// Check ownership and valid state
$stmt = $conn->prepare("
    SELECT a.*, d.id as doctor_id, u.full_name as doctor_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.id = :id AND a.patient_id = :pid
");
$stmt->execute([':id' => $appointment_id, ':pid' => $patient_id]);
$appt = $stmt->fetch();

if (!$appt) {
    die("Цаг олдсонгүй.");
}

$is_future = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) > time();
if (!$is_future || !in_array($appt['status'], ['pending', 'approved'])) {
    die("Энэ цагийг солих боломжгүй байна.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF алдаа.";
    } else {
        $new_slot_id = (int)$_POST['slot_id'];
        
        if ($new_slot_id > 0 && $new_slot_id != $appt['slot_id']) {
            try {
                $conn->beginTransaction();

                // Lock new slot
                $stmt_slot = $conn->prepare("SELECT slot_date, slot_time, is_booked FROM doctor_slots WHERE id = :sid AND doctor_id = :did FOR UPDATE");
                $stmt_slot->execute([':sid' => $new_slot_id, ':did' => $appt['doctor_id']]);
                $new_slot = $stmt_slot->fetch();

                if (!$new_slot || $new_slot['is_booked']) {
                    throw new Exception("Сонгосон шинэ цаг өөр хүнд захиалагдсан байна.");
                }

                // Release old slot
                if ($appt['slot_id']) {
                    $release_old = $conn->prepare("UPDATE doctor_slots SET is_booked = 0 WHERE id = :id");
                    $release_old->execute([':id' => $appt['slot_id']]);
                }

                // Book new slot
                $book_new = $conn->prepare("UPDATE doctor_slots SET is_booked = 1 WHERE id = :id");
                $book_new->execute([':id' => $new_slot_id]);

                // Update appointment
                $update_appt = $conn->prepare("
                    UPDATE appointments 
                    SET slot_id = :sid, appointment_date = :date, appointment_time = :time, status = 'pending' 
                    WHERE id = :id
                ");
                $update_appt->execute([
                    ':sid' => $new_slot_id,
                    ':date' => $new_slot['slot_date'],
                    ':time' => $new_slot['slot_time'],
                    ':id' => $appointment_id
                ]);

                $conn->commit();
                
                $_SESSION['success_msg'] = "Цаг захиалгын мэдээлэл амжилттай солигдлоо. Эмч рүү зөвшөөрөл илгээгдлээ.";
                header("Location: appointment_detail.php?id=" . $appointment_id);
                exit();

            } catch (Exception $e) {
                $conn->rollBack();
                $error = $e->getMessage();
            }
        } else {
            $error = "Та шинэ цаг сонгоогүй эсвэл хуучин цагаа дахин сонгосон байна.";
        }
    }
}

// Ensure the JS functionality is included
require_once "../includes/header.php";
?>

<div class="row" style="margin-top: 2rem;">
    <div class="col-md-8 mx-auto">
        <div style="margin-bottom: 20px;">
            <a href="appointment_detail.php?id=<?php echo $appointment_id; ?>" style="color: #64748b; text-decoration: none;">&larr; Буцах</a>
        </div>

        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 2rem; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Цаг солих</h3>
            <p style="color: #64748b; margin-bottom: 20px;">Та <strong>Др. <?php echo esc($appt['doctor_name']); ?></strong> эмчтэй товлосон цагийнхаа шинэ огноо, цагийг доор сонгоно уу.</p>
            
            <?php if (!empty($error)) echo render_alert($error, 'danger'); ?>

            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <strong>Одоогийн цаг:</strong><br>
                Огноо: <?php echo date('Y-m-d', strtotime($appt['appointment_date'])); ?><br>
                Цаг: <?php echo date('H:i', strtotime($appt['appointment_time'])); ?>
            </div>

            <form method="POST" action="">
                <?php echo render_csrf_field(); ?>
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <!-- Selected slot ID will be populated here -->
                <input type="hidden" name="slot_id" id="selected_slot_id" value="">
                <input type="hidden" id="doctor_id" value="<?php echo $appt['doctor_id']; ?>">

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;" for="appointment_date">Шинэ огноо сонгох</label>
                    <input type="date" id="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" style="max-width: 250px;">
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;">Боломжтой цагууд (өдөрөө сонгоод хүлээнэ үү)</label>
                    <div id="time-slots-container" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 80px;">
                        <div style="color: #94a3b8;">Эхлээд шинэ огноо сонгоно уу...</div>
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.1rem; border-radius: 8px;" disabled>Шинэ цагийг хадгалах</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const docId = document.getElementById('doctor_id').value;
    const dateInput = document.getElementById('appointment_date');
    const timeSlotsContainer = document.getElementById('time-slots-container');
    const selectedSlotInput = document.getElementById('selected_slot_id');
    const submitBtn = document.getElementById('submit-btn');

    dateInput.addEventListener('change', function() {
        if (this.value) {
            fetchSlots(docId, this.value);
        } else {
            timeSlotsContainer.innerHTML = '<div style="color: #94a3b8;">Эхлээд шинэ огноо сонгоно уу...</div>';
            submitBtn.disabled = true;
            selectedSlotInput.value = '';
        }
    });

    async function fetchSlots(doctorId, dateStr) {
        timeSlotsContainer.innerHTML = '<div style="color: #64748b;">Цаг шалгаж байна...</div>';
        selectedSlotInput.value = '';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch(`get_slots.php?doctor_id=${doctorId}&date=${dateStr}`);
            const data = await response.json();
            
            timeSlotsContainer.innerHTML = '';
            
            if (data.error) {
                const errDiv = document.createElement('div');
                errDiv.style.color = 'red';
                errDiv.textContent = data.error;
                timeSlotsContainer.appendChild(errDiv);
                return;
            }

            if (!data.slots || data.slots.length === 0) {
                timeSlotsContainer.innerHTML = '<div style="color: #f59e0b;">Энэ өдөр сул цаг байхгүй байна.</div>';
                return;
            }

            const slotGrid = document.createElement('div');
            slotGrid.style.display = 'flex';
            slotGrid.style.gap = '10px';
            slotGrid.style.flexWrap = 'wrap';

            data.slots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'slot-btn';
                btn.textContent = slot.time.substring(0, 5);
                btn.dataset.id = slot.id;
                
                btn.style.padding = '10px 20px';
                btn.style.border = '1px solid #cbd5e1';
                btn.style.borderRadius = '5px';
                btn.style.background = '#f8fafc';
                btn.style.cursor = 'pointer';
                
                btn.addEventListener('mouseenter', () => { if(selectedSlotInput.value != slot.id) btn.style.background = '#e2e8f0'; });
                btn.addEventListener('mouseleave', () => { if(selectedSlotInput.value != slot.id) btn.style.background = '#f8fafc'; });

                btn.addEventListener('click', () => {
                    document.querySelectorAll('.slot-btn').forEach(b => {
                        b.style.background = '#f8fafc';
                        b.style.borderColor = '#cbd5e1';
                        b.style.color = '#000';
                    });
                    btn.style.background = '#0284c7';
                    btn.style.borderColor = '#0284c7';
                    btn.style.color = '#fff';

                    selectedSlotInput.value = slot.id;
                    submitBtn.disabled = false;
                });
                
                slotGrid.appendChild(btn);
            });

            timeSlotsContainer.appendChild(slotGrid);
        } catch (error) {
            timeSlotsContainer.innerHTML = '<div style="color: red;">Алдаа гарлаа.</div>';
        }
    }
});
</script>

<?php require_once "../includes/footer.php"; ?>
