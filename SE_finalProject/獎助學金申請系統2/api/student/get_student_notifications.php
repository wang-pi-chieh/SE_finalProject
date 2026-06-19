<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/matching_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    wu_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$studentUsername = isset($_GET['student_username']) ? mb_substr(trim((string) $_GET['student_username']), 0, 50) : '';
$sync = isset($_GET['sync']) && in_array(strtolower((string) $_GET['sync']), ['1', 'true', 'yes'], true);
$unreadOnly = isset($_GET['unread_only']) && in_array(strtolower((string) $_GET['unread_only']), ['1', 'true', 'yes'], true);

wu_ensure_matching_schema($conn);
wu_validate_student_username($conn, $studentUsername);

if ($sync) {
    wu_sync_student_notifications($conn, $studentUsername);
}

$notifications = wu_fetch_student_notifications($conn, $studentUsername, $unreadOnly);
$unreadCount = wu_count_unread_notifications($conn, $studentUsername);

wu_json_response([
    'success' => true,
    'data' => $notifications,
    'unread_count' => $unreadCount,
]);
?>
