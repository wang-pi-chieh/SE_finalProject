<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_archive_schema($conn);

$stmt = $conn->prepare("
    SELECT id, archive_name, source_table, record_count, archive_path, file_size,
           downloaded_at, downloaded_by,
           original_deleted_at, original_deleted_by, original_deleted_count,
           created_by, created_at
    FROM data_archives
    ORDER BY created_at DESC
    LIMIT 30
");

if (!$stmt || !$stmt->execute()) {
    admin_ops_json(['success' => false, 'message' => '讀取資料封存紀錄失敗。'], 500);
}

$result = $stmt->get_result();
$archives = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['archive_path'])) {
        $absolute_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row['archive_path']);
        $row['file_exists'] = is_file($absolute_path);
    } else {
        $row['file_exists'] = false;
    }
    $archives[] = $row;
}

$stmt->close();
$conn->close();

admin_ops_json(['success' => true, 'data' => $archives]);
?>
