<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/matching_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wu_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = wu_read_json_body();
$studentUsername = isset($input['student_username']) ? mb_substr(trim((string) $input['student_username']), 0, 50) : '';
$notificationId = isset($input['notification_id']) ? (int) $input['notification_id'] : 0;
$markAll = !empty($input['mark_all']);

wu_ensure_matching_schema($conn);
wu_validate_student_username($conn, $studentUsername);

if ($markAll) {
    $stmt = $conn->prepare('UPDATE student_notifications SET is_read = 1 WHERE student_username = ?');
    if (!$stmt) {
        wu_json_response(['success' => false, 'message' => 'Database connection failed: 無法更新通知'], 500);
    }
    $stmt->bind_param('s', $studentUsername);
} else {
    if ($notificationId <= 0) {
        wu_json_response(['success' => false, 'message' => 'Input invalid: 缺少通知編號'], 400);
    }
    $stmt = $conn->prepare('UPDATE student_notifications SET is_read = 1 WHERE id = ? AND student_username = ?');
    if (!$stmt) {
        wu_json_response(['success' => false, 'message' => 'Database connection failed: 無法更新通知'], 500);
    }
    $stmt->bind_param('is', $notificationId, $studentUsername);
}

if (!$stmt->execute()) {
    $message = mb_substr($stmt->error ?: '更新通知失敗', 0, 255);
    $stmt->close();
    wu_json_response(['success' => false, 'message' => 'Database connection failed: ' . $message], 500);
}
$stmt->close();

$unreadCount = wu_count_unread_notifications($conn, $studentUsername);
wu_json_response([
    'success' => true,
    'message' => '已標記通知為已讀',
    'unread_count' => $unreadCount,
]);
?>
