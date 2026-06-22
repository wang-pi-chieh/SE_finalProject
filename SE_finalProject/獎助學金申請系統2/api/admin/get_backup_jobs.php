<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';
require_once __DIR__ . '/_backup_storage.php';

admin_ops_require_table($conn, 'backup_jobs');

$stmt = $conn->prepare("
    SELECT id, job_name, status, requested_by, file_path, message, created_at, updated_at
    FROM backup_jobs
    ORDER BY created_at DESC
    LIMIT 50
");

if (!$stmt || !$stmt->execute()) {
    admin_ops_json(['success' => false, 'message' => '讀取備份工作失敗'], 500);
}

$result = $stmt->get_result();
$jobs = [];
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'completed' && !empty($row['file_path'])) {
        if (admin_backup_resolve_file_path($row['file_path']) === null) {
            $row['status'] = 'failed';
            $row['message'] = '備份紀錄不一致：找不到 ZIP 檔案。';

            $update_stmt = $conn->prepare("UPDATE backup_jobs SET status = ?, message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            if ($update_stmt) {
                $failed_status = 'failed';
                $update_stmt->bind_param("ssi", $failed_status, $row['message'], $row['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    }
    $jobs[] = $row;
}

$stmt->close();
$conn->close();

admin_ops_json(['success' => true, 'data' => $jobs]);
?>
