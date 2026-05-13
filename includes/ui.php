<?php
// Shared UI helper functions

/**
 * Creates an alert message.
 * @param string $message The message text.
 * @param string $type The alert type (success, danger, info, warning).
 * @return string HTML alert string.
 */
function render_alert($message, $type = 'info') {
    $title = 'Мэдээлэл';
    $icon = 'ℹ️';
    switch ($type) {
        case 'success':
            $title = 'Амжилттай';
            $icon = '✅';
            break;
        case 'danger':
        case 'error':
            $type = 'danger'; // for CSS class safety
            $title = 'Алдаа';
            $icon = '❌';
            break;
        case 'warning':
            $title = 'Анхааруулга';
            $icon = '⚠️';
            break;
    }

    return sprintf(
        '<div class="alert alert-%s">
            <strong>%s</strong> %s
        </div>',
        htmlspecialchars($type, ENT_QUOTES),
        $icon . ' ' . $title . ':',
        esc($message)
    );
}

/**
 * Renders a stylized status badge for appointments.
 * @param string $status
 * @return string HTML badge
 */
function render_status_badge($status) {
    $class = 'badge-secondary';
    $label = 'Тодорхойгүй';

    switch ($status) {
        case 'pending':
            $class = 'badge-warning';
            $label = 'Хүлээгдэж буй';
            break;
        case 'approved':
            $class = 'badge-success';
            $label = 'Баталгаажсан';
            break;
        case 'completed':
            $class = 'badge-info';
            $label = 'Дууссан';
            break;
        case 'cancelled':
            $class = 'badge-danger';
            $label = 'Цуцлагдсан';
            break;
    }

    return sprintf('<span class="badge %s">%s</span>', $class, $label);
}

/**
 * Renders an empty state placeholder.
 * @param string $title
 * @param string $description
 * @param string|null $actionLabel
 * @param string|null $actionUrl
 * @return string HTML empty state
 */
function render_empty_state($title, $description, $actionLabel = null, $actionUrl = null) {
    $actionHtml = '';
    if ($actionLabel && $actionUrl) {
        $actionHtml = sprintf(
            '<a href="%s" class="btn btn-primary" style="margin-top: 15px; display: inline-block;">%s</a>',
            esc($actionUrl),
            esc($actionLabel)
        );
    }

    return sprintf(
        '<div class="empty-state text-center" style="padding: 40px 20px; border: 1px dashed #ced4da; border-radius: 8px; background: #f8fafc; margin: 20px 0;">
            <div style="font-size: 40px; color: #94a3b8; margin-bottom: 10px;">🩺</div>
            <h4 style="color: #475569; margin-bottom: 5px;">%s</h4>
            <p style="color: #64748b; margin: 0;">%s</p>
            %s
        </div>',
        esc($title),
        esc($description),
        $actionHtml
    );
}

/**
 * Returns a hidden CSRF token input field.
 * @return string HTML input
 */
function render_csrf_field() {
    $token = generate_csrf_token();
    return sprintf('<input type="hidden" name="csrf_token" value="%s">', esc($token));
}

/**
 * Checks if the current path matches the given path and returns 'active'.
 * @param string $path
 * @return string 'active' or empty string
 */
function active_nav($path) {
    $current = basename($_SERVER['PHP_SELF']);
    return ($current === $path) ? 'active' : '';
}
?>