<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
require_once __DIR__ . '/_admin_ops_common.php';
require_once __DIR__ . '/_backup_storage.php';

admin_ops_require_table($conn, 'backup_jobs');

$data = json_decode(file_get_contents('php://input'), true);
$operator = isset($data['operator']) && trim($data['operator']) !== '' ? trim($data['operator']) : admin_ops_actor();
$job_name = 'backup_job_' . date('Ymd_His');
$status = 'running';
$message = '備份建立中。';

$stmt = $conn->prepare("INSERT INTO backup_jobs (job_name, status, requested_by, message) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    admin_ops_json(['success' => false, 'message' => '建立備份工作語句失敗'], 500);
}

$stmt->bind_param("ssss", $job_name, $status, $operator, $message);
if (!$stmt->execute()) {
    admin_ops_json(['success' => false, 'message' => '建立備份工作失敗'], 500);
}

$job_id = $stmt->insert_id;
$stmt->close();

$zip_filename = $job_name . '.zip';
try {
    $target = admin_backup_prepare_target($zip_filename);
    $zip_path = $target['absolute_path'];
    $relative_path = $target['relative_path'];
    $sql_dump = build_database_dump($conn);

    if (!admin_backup_write_zip($zip_path, ['database_dump.sql' => $sql_dump])) {
        throw new Exception('無法建立 ZIP 檔案。');
    }
} catch (Throwable $e) {
    mark_backup_job_failed($conn, $job_id, $e->getMessage());
    admin_ops_json(['success' => false, 'message' => $e->getMessage()], 500);
}

$completed_status = 'completed';
$completed_message = '備份已完成，可下載 ZIP 檔。';
$update_stmt = $conn->prepare("UPDATE backup_jobs SET status = ?, file_path = ?, message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
if (!$update_stmt) {
    mark_backup_job_failed($conn, $job_id, '備份完成，但更新資料庫狀態失敗。');
    admin_ops_json(['success' => false, 'message' => '備份完成，但更新資料庫狀態失敗'], 500);
}

$update_stmt->bind_param("sssi", $completed_status, $relative_path, $completed_message, $job_id);
$update_stmt->execute();
$update_stmt->close();

logAction($operator, '建立備份工作', "建立備份工作 #{$job_id}: {$job_name}，已產生 ZIP 備份");
$conn->close();

admin_ops_json([
    'success' => true,
    'data' => [
        'id' => $job_id,
        'job_name' => $job_name,
        'status' => $completed_status,
        'file_path' => $relative_path,
        'download_url' => "download_backup_job.php?id={$job_id}"
    ]
]);

function build_database_dump($conn)
{
    $sql = "-- Scholarship System Database Dump\n";
    $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
    }

    foreach ($tables as $table) {
        $escaped_table = str_replace('`', '``', $table);
        $create_result = $conn->query("SHOW CREATE TABLE `{$escaped_table}`");
        if (!$create_result) {
            continue;
        }

        $create_row = $create_result->fetch_row();
        $sql .= "-- Table structure for `{$escaped_table}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$escaped_table}`;\n";
        $sql .= $create_row[1] . ";\n\n";

        $data_result = $conn->query("SELECT * FROM `{$escaped_table}`");
        if (!$data_result) {
            continue;
        }

        $field_count = $data_result->field_count;
        $sql .= "-- Dumping data for table `{$escaped_table}`\n";
        while ($row = $data_result->fetch_row()) {
            $values = [];
            for ($i = 0; $i < $field_count; $i++) {
                if ($row[$i] === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . $conn->real_escape_string($row[$i]) . "'";
                }
            }
            $sql .= "INSERT INTO `{$escaped_table}` VALUES(" . implode(',', $values) . ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    return $sql;
}

function mark_backup_job_failed($conn, $job_id, $message)
{
    $status = 'failed';
    $stmt = $conn->prepare("UPDATE backup_jobs SET status = ?, message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $status, $message, $job_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
