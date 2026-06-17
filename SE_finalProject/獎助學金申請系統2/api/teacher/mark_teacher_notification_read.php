<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../admin/_admin_ops_common.php';

header('Content-Type: application/json; charset=utf-8');

admin_ops_ensure_teacher_notification_schema($conn);

$payload = json_decode(file_get_contents('php://input'), true);
$username = isset($payload['teacher_username']) ? trim($payload['teacher_username']) : '';
$notification_id = isset($payload['notification_id']) ? (int) $payload['notification_id'] : 0;
$mark_all = !empty($payload['mark_all']);

if ($username === '') {
    echo json_encode(['success' => false, 'message' => '缺少老師帳號'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($mark_all) {
    $stmt = $conn->prepare("UPDATE teacher_notifications SET is_read = 1 WHERE teacher_username = ? AND is_read = 0");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => '更新通知失敗'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt->bind_param("s", $username);
} else {
    if ($notification_id <= 0) {
        echo json_encode(['success' => false, 'message' => '缺少通知編號'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $conn->prepare("UPDATE teacher_notifications SET is_read = 1 WHERE id = ? AND teacher_username = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => '更新通知失敗'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt->bind_param("is", $notification_id, $username);
}

$stmt->execute();
$updated = $stmt->affected_rows >= 0;
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
    'success' => $updated,
    'message' => $updated ? '已標記為已讀' : '更新通知失敗',
    'unread_count' => $unread_count
], JSON_UNESCAPED_UNICODE);
?>
