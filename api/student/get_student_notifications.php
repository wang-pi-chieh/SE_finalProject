<?php
// api/student/get_student_notifications.php
header('Content-Type: application/json; charset=utf-8');
require '../db_connect.php';
require 'matching_utils.php';

$username = trim($_GET['student_username'] ?? '');
$unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';
$sync = !isset($_GET['sync']) || $_GET['sync'] !== '0';

if ($username === '') {
    echo json_encode(['success' => false, 'message' => 'Missing student_username']);
    exit;
}

if (!wu_table_exists($conn, 'student_notifications')) {
    echo json_encode([
        'success' => false,
        'message' => '請先執行 migrations/005_wu_notification_matching.sql',
        'data' => [],
        'unread_count' => 0,
    ]);
    exit;
}

if ($sync) {
    wu_sync_student_notifications($conn, $username);
}

$data = wu_fetch_student_notifications($conn, $username, $unreadOnly);
$unreadCount = wu_count_unread_notifications($conn, $username);

echo json_encode([
    'success' => true,
    'data' => $data,
    'unread_count' => $unreadCount,
]);

$conn->close();
