<?php
// Notification helpers — require db.php before including this file

function create_notification(PDO $conn, int $userId, string $title, string $message): void {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:uid, :title, :msg)");
    $stmt->execute([':uid' => $userId, ':title' => $title, ':msg' => $message]);
}

function get_unread_notification_count(PDO $conn, int $userId): int {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $userId]);
    return (int)$stmt->fetchColumn();
}

function get_notifications(PDO $conn, int $userId, int $limit = 50): array {
    $stmt = $conn->prepare("
        SELECT id, title, message, is_read, created_at
        FROM notifications
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mark_notification_read(PDO $conn, int $notificationId, int $userId): void {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
}

function mark_all_notifications_read(PDO $conn, int $userId): void {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $userId]);
}
