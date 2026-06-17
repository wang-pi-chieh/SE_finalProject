<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_teacher_notification_schema($conn);
$conn->close();

admin_ops_json([
    'success' => true,
    'message' => 'teacher_notifications 資料表已確認存在。'
]);
?>
