<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../admin/_admin_ops_common.php';

header('Content-Type: application/json; charset=utf-8');

admin_ops_ensure_teacher_notification_schema($conn);

$username = isset($_GET['teacher_username']) ? trim($_GET['teacher_username']) : '';
if ($username === '') {
    echo json_encode(['success' => true, 'data' => [], 'unread_count' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, type, title, message, related_application_id, related_issue_report_id, is_read, created_at
    FROM teacher_notifications
    WHERE teacher_username = ?
    ORDER BY is_read ASC, created_at DESC, id DESC
    LIMIT 50
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => '讀取老師通知失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['related_application_id'] = $row['related_application_id'] !== null ? (int) $row['related_application_id'] : null;
    $row['related_issue_report_id'] = $row['related_issue_report_id'] !== null ? (int) $row['related_issue_report_id'] : null;
    $row['is_read'] = (int) $row['is_read'];
    $data[] = $row;
}
$stmt->close();

$count_stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM teacher_notifications WHERE teacher_username = ? AND is_read = 0");
$unread_count = 0;
if ($count_stmt) {
    $count_stmt->bind_param("s", $username);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result ? $count_result->fetch_assoc() : null;
    $unread_count = $count_row ? (int) $count_row['unread_count'] : 0;
    $count_stmt->close();
}

echo json_encode([
    'success' => true,
    'data' => $data,
    'unread_count' => $unread_count
], JSON_UNESCAPED_UNICODE);
?>
