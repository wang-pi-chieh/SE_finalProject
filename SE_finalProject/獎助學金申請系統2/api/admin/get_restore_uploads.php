<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_restore_schema($conn);

$stmt = $conn->prepare("
    SELECT
        ru.id,
        ru.restore_log_id,
        ru.original_name,
        ru.stored_name,
        ru.stored_path,
        ru.file_size,
        ru.uploaded_by,
        ru.created_at,
        rl.status,
        rl.message
    FROM restore_uploads ru
    LEFT JOIN restore_logs rl ON rl.id = ru.restore_log_id
    ORDER BY ru.created_at DESC
    LIMIT 30
");

if (!$stmt || !$stmt->execute()) {
    admin_ops_json(['success' => false, 'message' => '讀取 SQL 還原上傳紀錄失敗。'], 500);
}

$result = $stmt->get_result();
$uploads = [];
while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
}

$stmt->close();
$conn->close();

admin_ops_json([
    'success' => true,
    'data' => $uploads,
    'phpmyadmin_url' => 'http://localhost/phpmyadmin/index.php?route=/database/import&db=se_finalproject'
]);
?>
