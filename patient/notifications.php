<?php
require_once "../config/security.php";
require_once "../config/db.php";
require_once "../includes/notifications.php";
require_auth('patient');

$user_id = $_SESSION["user_id"];

// Mark all read on visit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_all'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    mark_all_notifications_read($conn, $user_id);
    header("Location: notifications.php");
    exit();
}

$notifications = get_notifications($conn, $user_id);

require_once "../includes/header.php";
?>

<div class="glass-card" style="margin-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0; color: #1e293b;">Мэдэгдлүүд</h2>
        <?php if (!empty($notifications)): ?>
        <form method="POST" action="">
            <?php echo render_csrf_field(); ?>
            <input type="hidden" name="mark_all" value="1">
            <button type="submit" class="btn btn-secondary" style="font-size: 0.85rem; padding: 6px 14px;">Бүгдийг уншсан болгох</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">🔔</div>
            <p style="margin: 0;">Одоогоор мэдэгдэл байхгүй байна.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($notifications as $n): ?>
            <div style="background: <?php echo $n['is_read'] ? '#f8fafc' : '#eff6ff'; ?>; border: 1px solid <?php echo $n['is_read'] ? '#e2e8f0' : '#bfdbfe'; ?>; border-radius: 8px; padding: 15px 18px; border-left: 4px solid <?php echo $n['is_read'] ? '#cbd5e1' : '#3b82f6'; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
                    <div>
                        <strong style="color: #1e293b; font-size: 0.95rem;"><?php echo esc($n['title']); ?></strong>
                        <?php if (!$n['is_read']): ?>
                            <span style="display: inline-block; background: #3b82f6; color: white; font-size: 0.65rem; padding: 1px 6px; border-radius: 10px; margin-left: 6px; vertical-align: middle;">Шинэ</span>
                        <?php endif; ?>
                        <p style="margin: 5px 0 0; color: #475569; font-size: 0.9rem;"><?php echo nl2br(esc($n['message'])); ?></p>
                    </div>
                    <span style="color: #94a3b8; font-size: 0.8rem; white-space: nowrap;"><?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
