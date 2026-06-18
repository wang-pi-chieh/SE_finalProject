<?php
require_once __DIR__ . '/../common/mailer.php';

function reviewer_disbursement_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function reviewer_disbursement_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS count
         FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?"
    );
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ((int) ($row['count'] ?? 0)) > 0;
}

function reviewer_disbursement_column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS count
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ((int) ($row['count'] ?? 0)) > 0;
}

function reviewer_disbursement_ensure_table(mysqli $conn): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS award_disbursements (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            status enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
            handled_by varchar(50) DEFAULT NULL,
            handled_at datetime DEFAULT NULL,
            note varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT current_timestamp(),
            updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id),
            UNIQUE KEY uniq_award_disbursements_application (application_id),
            KEY idx_award_disbursements_status_updated (status, updated_at),
            KEY idx_award_disbursements_handler (handled_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if (!$conn->query($sql)) {
        throw new Exception('建立撥款資料表失敗：' . $conn->error);
    }
}

function reviewer_disbursement_actor(mysqli $conn, string $username): ?array
{
    $stmt = $conn->prepare("SELECT username, role, real_name FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function reviewer_disbursement_is_admin_role(string $role): bool
{
    return in_array($role, ['系統管理員', 'system_admin', 'admin'], true);
}

function reviewer_disbursement_is_unit_role(string $role): bool
{
    return in_array($role, ['獎助單位', 'scholarship_unit', 'reviewer'], true);
}

function reviewer_disbursement_scope(array $actor, string &$types, array &$params): string
{
    $role = (string) ($actor['role'] ?? '');
    if (reviewer_disbursement_is_admin_role($role)) {
        return '';
    }

    if (reviewer_disbursement_is_unit_role($role)) {
        $types .= 's';
        $params[] = (string) $actor['username'];
        return ' AND s.provider_username = ?';
    }

    throw new Exception('沒有撥款管理權限');
}

function reviewer_disbursement_sync_approved(mysqli $conn, array $actor): void
{
    $types = '';
    $params = [];
    $scope = reviewer_disbursement_scope($actor, $types, $params);
    $sql = "
        INSERT IGNORE INTO award_disbursements (application_id)
        SELECT a.id
        FROM applications a
        INNER JOIN scholarships s ON s.id = a.scholarship_id
        WHERE a.status = 1 {$scope}
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('準備同步撥款資料失敗：' . $conn->error);
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        throw new Exception('同步撥款資料失敗：' . $stmt->error);
    }
}

function reviewer_disbursement_fetch(mysqli $conn, int $id, array $actor): ?array
{
    $types = 'i';
    $params = [$id];
    $scope = reviewer_disbursement_scope($actor, $types, $params);
    $sql = "
        SELECT
            d.id,
            d.application_id,
            d.status,
            d.handled_by,
            d.handled_at,
            d.note,
            d.created_at,
            d.updated_at,
            a.student_username,
            a.scholarship_id,
            COALESCE(u.real_name, a.student_username) AS student_name,
            u.email AS student_email,
            s.name AS scholarship_name,
            s.amount,
            s.provider_username
        FROM award_disbursements d
        INNER JOIN applications a ON a.id = d.application_id
        INNER JOIN scholarships s ON s.id = a.scholarship_id
        LEFT JOIN users u ON u.username = a.student_username
        WHERE d.id = ? {$scope}
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('準備讀取撥款資料失敗：' . $conn->error);
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function reviewer_disbursement_status_label(string $status): string
{
    $labels = [
        'pending' => '待撥款',
        'paid' => '已撥款',
        'failed' => '撥款失敗',
    ];
    return $labels[$status] ?? '待撥款';
}

function reviewer_disbursement_email_error(string $message): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($message, 0, 255, 'UTF-8');
    }
    return substr($message, 0, 255);
}

function reviewer_disbursement_mark_notification_email(mysqli $conn, int $id, bool $sent, ?string $error): void
{
    if (!reviewer_disbursement_column_exists($conn, 'student_notifications', 'email_sent_at')
        || !reviewer_disbursement_column_exists($conn, 'student_notifications', 'email_last_error')) {
        return;
    }

    $safeError = $sent ? null : reviewer_disbursement_email_error((string) $error);
    $sentInt = $sent ? 1 : 0;
    $stmt = $conn->prepare(
        "UPDATE student_notifications
         SET email_sent_at = CASE WHEN ? = 1 THEN NOW() ELSE email_sent_at END,
             email_last_error = ?
         WHERE id = ?"
    );
    $stmt->bind_param('isi', $sentInt, $safeError, $id);
    $stmt->execute();
}

function reviewer_disbursement_notify_student(mysqli $conn, array $disbursement): array
{
    if (!reviewer_disbursement_table_exists($conn, 'student_notifications')) {
        return ['created' => false, 'email_sent' => false, 'error' => 'student_notifications 資料表不存在'];
    }

    $status = (string) $disbursement['status'];
    $studentUsername = (string) $disbursement['student_username'];
    $scholarshipName = (string) ($disbursement['scholarship_name'] ?: '獎助項目');
    $applicationId = (int) $disbursement['application_id'];
    $scholarshipId = (int) $disbursement['scholarship_id'];
    $label = reviewer_disbursement_status_label($status);
    $title = '撥款狀態更新：' . $label;
    $message = "您申請的「{$scholarshipName}」撥款狀態已更新為「{$label}」。";
    $dedupKey = "disbursement-{$applicationId}-{$status}";

    $hasEmailColumns = reviewer_disbursement_column_exists($conn, 'student_notifications', 'email_sent_at')
        && reviewer_disbursement_column_exists($conn, 'student_notifications', 'email_last_error');

    if ($hasEmailColumns) {
        $sql = "
            INSERT INTO student_notifications
                (student_username, type, title, message, related_application_id, related_scholarship_id, dedup_key, is_read, email_sent_at, email_last_error)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, NULL, NULL)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                message = VALUES(message),
                is_read = 0
        ";
    } else {
        $sql = "
            INSERT INTO student_notifications
                (student_username, type, title, message, related_application_id, related_scholarship_id, dedup_key, is_read)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                message = VALUES(message),
                is_read = 0
        ";
    }

    $type = 'disbursement_' . $status;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['created' => false, 'email_sent' => false, 'error' => '建立通知失敗：' . $conn->error];
    }
    $stmt->bind_param('ssssiis', $studentUsername, $type, $title, $message, $applicationId, $scholarshipId, $dedupKey);
    if (!$stmt->execute()) {
        return ['created' => false, 'email_sent' => false, 'error' => '寫入通知失敗：' . $stmt->error];
    }

    $idStmt = $conn->prepare("SELECT id, email_sent_at FROM student_notifications WHERE dedup_key = ? LIMIT 1");
    $idStmt->bind_param('s', $dedupKey);
    $idStmt->execute();
    $notification = $idStmt->get_result()->fetch_assoc();
    $notificationId = (int) ($notification['id'] ?? 0);
    $alreadySent = !empty($notification['email_sent_at']);

    if (!$hasEmailColumns || $notificationId <= 0 || $alreadySent) {
        return ['created' => true, 'email_sent' => $alreadySent, 'error' => null];
    }

    $email = trim((string) ($disbursement['student_email'] ?? ''));
    if ($email === '') {
        reviewer_disbursement_mark_notification_email($conn, $notificationId, false, '找不到學生 Email');
        return ['created' => true, 'email_sent' => false, 'error' => '找不到學生 Email'];
    }

    $emailError = null;
    $html = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $sent = wu_send_gmail_notification(
        $email,
        (string) ($disbursement['student_name'] ?: $studentUsername),
        '【NSAMS】' . $title,
        $html,
        $emailError
    );
    reviewer_disbursement_mark_notification_email($conn, $notificationId, $sent, $emailError);

    return ['created' => true, 'email_sent' => $sent, 'error' => $sent ? null : $emailError];
}
?>
