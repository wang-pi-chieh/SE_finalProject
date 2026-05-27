<?php
// api/student/mark_notification_read.php
header('Content-Type: application/json; charset=utf-8');
require '../db_connect.php';
require 'matching_utils.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$username = trim($input['student_username'] ?? '');
$notificationId = isset($input['notification_id']) ? (int) $input['notification_id'] : 0;
$markAll = isset($input['mark_all']) && ($input['mark_all'] === true || $input['mark_all'] === '1' || $input['mark_all'] === 1);

if ($username === '') {
    echo json_encode(['success' => false, 'message' => 'Missing student_username']);
    exit;
}

if (!wu_table_exists($conn, 'student_notifications')) {
    echo json_encode(['success' => false, 'message' => '請先執行 migrations/005_wu_notification_matching.sql']);
    exit;
}

if ($markAll) {
    $stmt = $conn->prepare(
        'UPDATE student_notifications SET is_read = 1 WHERE student_username = ? AND is_read = 0'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => '已全部標記為已讀',
        'updated_count' => $affected,
        'unread_count' => 0,
    ]);
    exit;
}

if ($notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing notification_id']);
    exit;
}

$stmt = $conn->prepare(
    'UPDATE student_notifications SET is_read = 1 WHERE id = ? AND student_username = ?'
);
$stmt->bind_param('is', $notificationId, $username);
$stmt->execute();
$updated = $stmt->affected_rows > 0;
$stmt->close();

echo json_encode([
    'success' => $updated,
    'message' => $updated ? '已標記為已讀' : '找不到通知或無權限',
    'unread_count' => wu_count_unread_notifications($conn, $username),
]);

$conn->close();
