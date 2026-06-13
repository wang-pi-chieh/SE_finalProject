<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
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
        $stmt->close();
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
    }
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
