<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
require_once __DIR__ . '/../common/mailer.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_issue_schema($conn);
admin_ops_ensure_teacher_notification_schema($conn);

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? (int) $data['id'] : 0;
$status = isset($data['status']) ? trim($data['status']) : '';
$operator = isset($data['operator']) && trim($data['operator']) !== '' ? trim($data['operator']) : admin_ops_actor();
$allowed_statuses = ['open', 'processing', 'resolved'];

if ($id <= 0 || !in_array($status, $allowed_statuses, true)) {
    admin_ops_json(['success' => false, 'message' => '缺少有效的問題編號或狀態'], 400);
}

$old_stmt = $conn->prepare("SELECT reporter_username, title, status FROM issue_reports WHERE id = ? LIMIT 1");
if (!$old_stmt) {
    admin_ops_json(['success' => false, 'message' => '讀取問題回報失敗'], 500);
}

$old_stmt->bind_param("i", $id);
$old_stmt->execute();
$old_result = $old_stmt->get_result();
$old_report = $old_result ? $old_result->fetch_assoc() : null;
$old_stmt->close();

if (!$old_report) {
    admin_ops_json(['success' => false, 'message' => '找不到指定的問題回報'], 404);
}

$stmt = $conn->prepare("UPDATE issue_reports SET status = ?, handled_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
if (!$stmt) {
    admin_ops_json(['success' => false, 'message' => '建立更新語句失敗'], 500);
}

$stmt->bind_param("ssi", $status, $operator, $id);
if (!$stmt->execute()) {
    admin_ops_json(['success' => false, 'message' => '更新問題回報失敗'], 500);
}
$stmt->close();

$reporter_username = trim((string) $old_report['reporter_username']);
if ($old_report['status'] !== $status && $reporter_username !== '') {
    if (is_student_user($conn, $reporter_username)) {
        create_student_issue_notification($conn, $id, $reporter_username, $old_report['title'], $status);
    } elseif (is_teacher_user($conn, $reporter_username)) {
        create_teacher_issue_notification($conn, $id, $reporter_username, $old_report['title'], $status);
    } else {
        create_issue_report_notification($conn, $id, $reporter_username, $old_report['title'], $status);
    }
}

logAction($operator, '更新問題回報', "問題 #{$id} 狀態更新為 {$status}");
$conn->close();

admin_ops_json(['success' => true]);

function create_issue_report_notification($conn, $issue_id, $recipient_username, $issue_title, $status)
{
    $labels = [
        'open' => '待處理',
        'processing' => '處理中',
        'resolved' => '已解決'
    ];
    $label = isset($labels[$status]) ? $labels[$status] : $status;
    $title = '問題回報狀態更新';
    $message = "你提出的問題「{$issue_title}」狀態已更新為：{$label}";

    $stmt = $conn->prepare("
        INSERT INTO issue_report_notifications (issue_report_id, recipient_username, title, message)
        VALUES (?, ?, ?, ?)
    ");
    if ($stmt) {
        $stmt->bind_param("isss", $issue_id, $recipient_username, $title, $message);
        $stmt->execute();
        $notification_id = $stmt->insert_id;
        $stmt->close();
        $email_error = null;
        $email_sent = send_user_gmail_notification($conn, $recipient_username, $title, $message, $email_error);
        mark_notification_email_status(
            $conn,
            'issue_report_notifications',
            $notification_id,
            $email_sent,
            $email_error
        );
    }
}

function create_student_issue_notification($conn, $issue_id, $recipient_username, $issue_title, $status)
{
    if (!admin_ops_table_exists($conn, 'student_notifications')) {
        return;
    }

    if (!is_student_user($conn, $recipient_username)) {
        return;
    }

    $labels = [
        'open' => '待處理',
        'processing' => '處理中',
        'resolved' => '已解決'
    ];
    $label = isset($labels[$status]) ? $labels[$status] : $status;
    $type = 'issue_report_update';
    $title = '問題回報狀態更新';
    $message = "你提出的問題「{$issue_title}」狀態已更新為：{$label}";
    $dedup_key = "issue-report-{$issue_id}-{$status}";

    $stmt = $conn->prepare("
        INSERT INTO student_notifications
            (student_username, type, title, message, related_application_id, related_scholarship_id, dedup_key, is_read)
        VALUES (?, ?, ?, ?, NULL, NULL, ?, 0)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            message = VALUES(message),
            is_read = 0,
            created_at = CURRENT_TIMESTAMP
    ");
    if ($stmt) {
        $stmt->bind_param("sssss", $recipient_username, $type, $title, $message, $dedup_key);
        $stmt->execute();
        $stmt->close();
        $email_error = null;
        $email_sent = send_user_gmail_notification($conn, $recipient_username, $title, $message, $email_error);
        mark_dedup_notification_email_status(
            $conn,
            'student_notifications',
            $dedup_key,
            $email_sent,
            $email_error
        );
    }
}

function create_teacher_issue_notification($conn, $issue_id, $recipient_username, $issue_title, $status)
{
    if (!admin_ops_table_exists($conn, 'teacher_notifications')) {
        return;
    }

    if (!is_teacher_user($conn, $recipient_username)) {
        return;
    }

    $labels = [
        'open' => '待處理',
        'processing' => '處理中',
        'resolved' => '已解決'
    ];
    $label = isset($labels[$status]) ? $labels[$status] : $status;
    $type = 'issue_report_update';
    $title = '問題回報狀態更新';
    $message = "你提出的問題「{$issue_title}」狀態已更新為：{$label}";
    $dedup_key = "issue-report-{$issue_id}-{$status}";

    $stmt = $conn->prepare("
        INSERT INTO teacher_notifications
            (teacher_username, type, title, message, related_application_id, related_issue_report_id, dedup_key, is_read)
        VALUES (?, ?, ?, ?, NULL, ?, ?, 0)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            message = VALUES(message),
            is_read = 0,
            created_at = CURRENT_TIMESTAMP
    ");
    if ($stmt) {
        $stmt->bind_param("ssssis", $recipient_username, $type, $title, $message, $issue_id, $dedup_key);
        $stmt->execute();
        $stmt->close();
        $email_error = null;
        $email_sent = send_user_gmail_notification($conn, $recipient_username, $title, $message, $email_error);
        mark_dedup_notification_email_status(
            $conn,
            'teacher_notifications',
            $dedup_key,
            $email_sent,
            $email_error
        );
    }
}

function send_user_gmail_notification($conn, $username, $title, $message, &$error)
{
    $error = null;
    $stmt = $conn->prepare("SELECT real_name, email FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) {
        $error = '讀取使用者 Email 失敗';
        return false;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user || empty($user['email'])) {
        $error = '找不到使用者 Email';
        return false;
    }

    $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safe_message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $html = "
        <p>{$safe_message}</p>
        <p style=\"color:#64748b;font-size:12px;\">此信件由 NSAMS 獎助學金申請系統自動寄出。</p>
    ";

    return wu_send_gmail_notification(
        $user['email'],
        $user['real_name'] ?: $username,
        "【NSAMS】{$safe_title}",
        $html,
        $error
    );
}

function mark_notification_email_status($conn, $table, $notification_id, $success, $error)
{
    if ($notification_id <= 0 || !in_array($table, ['issue_report_notifications', 'teacher_notifications'], true)) {
        return;
    }

    if (!admin_ops_column_exists($conn, $table, 'email_sent_at') || !admin_ops_column_exists($conn, $table, 'email_last_error')) {
        return;
    }

    $safe_error = $success ? null : truncate_email_error($error);
    $stmt = $conn->prepare("
        UPDATE {$table}
        SET email_sent_at = CASE WHEN ? = 1 THEN NOW() ELSE email_sent_at END,
            email_last_error = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        return;
    }
    $success_int = $success ? 1 : 0;
    $stmt->bind_param("isi", $success_int, $safe_error, $notification_id);
    $stmt->execute();
    $stmt->close();
}

function mark_dedup_notification_email_status($conn, $table, $dedup_key, $success, $error)
{
    if ($dedup_key === '' || !in_array($table, ['student_notifications', 'teacher_notifications'], true)) {
        return;
    }

    if (!admin_ops_column_exists($conn, $table, 'email_sent_at') || !admin_ops_column_exists($conn, $table, 'email_last_error')) {
        return;
    }

    $safe_error = $success ? null : truncate_email_error($error);
    $stmt = $conn->prepare("
        UPDATE {$table}
        SET email_sent_at = CASE WHEN ? = 1 THEN NOW() ELSE email_sent_at END,
            email_last_error = ?
        WHERE dedup_key = ?
    ");
    if (!$stmt) {
        return;
    }
    $success_int = $success ? 1 : 0;
    $stmt->bind_param("iss", $success_int, $safe_error, $dedup_key);
    $stmt->execute();
    $stmt->close();
}

function truncate_email_error($error)
{
    $message = (string) ($error ?: 'Gmail 寄送失敗');
    if (function_exists('mb_substr')) {
        return mb_substr($message, 0, 255);
    }
    return substr($message, 0, 255);
}

function is_student_user($conn, $username)
{
    $stmt = $conn->prepare("
        SELECT u.username
        FROM users u
        INNER JOIN students s ON s.username = u.username
        WHERE u.username = ? AND (u.role = '學生' OR u.role = 'student')
        LIMIT 1
    ");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function is_teacher_user($conn, $username)
{
    $stmt = $conn->prepare("
        SELECT u.username
        FROM users u
        INNER JOIN teachers t ON t.username = u.username
        WHERE u.username = ? AND (u.role = '老師' OR u.role = 'teacher')
        LIMIT 1
    ");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}
?>
