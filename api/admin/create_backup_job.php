<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
require_once __DIR__ . '/_admin_ops_common.php';

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

$backup_dir = realpath(__DIR__ . '/../../');
if ($backup_dir === false) {
    mark_backup_job_failed($conn, $job_id, '無法取得專案目錄。');
    admin_ops_json(['success' => false, 'message' => '無法取得專案目錄'], 500);
}

$backup_dir .= DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backup_dir) && !mkdir($backup_dir, 0775, true)) {
    mark_backup_job_failed($conn, $job_id, '無法建立備份資料夾。');
    admin_ops_json(['success' => false, 'message' => '無法建立備份資料夾'], 500);
}

$zip_filename = $job_name . '.zip';
$zip_path = $backup_dir . DIRECTORY_SEPARATOR . $zip_filename;
$relative_path = 'backups/' . $zip_filename;
$sql_dump = build_database_dump($conn);

if (!create_backup_zip($zip_path, $sql_dump)) {
    mark_backup_job_failed($conn, $job_id, '無法建立 ZIP 檔案，請確認 PHP ZipArchive 或 PowerShell Compress-Archive 可用。');
    admin_ops_json(['success' => false, 'message' => '無法建立 ZIP 檔案，請確認 PHP ZipArchive 或 PowerShell Compress-Archive 可用。'], 500);
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

function create_backup_zip($zip_path, $sql_dump)
{
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $zip->addFromString('database_dump.sql', $sql_dump);
        $zip->close();

        return is_file($zip_path) && filesize($zip_path) > 0;
    }

    return create_backup_zip_with_powershell($zip_path, $sql_dump);
}

function create_backup_zip_with_powershell($zip_path, $sql_dump)
{
    if (!function_exists('exec')) {
        return false;
    }

    $tmp_root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wang_backup_' . bin2hex(random_bytes(6));
    if (!mkdir($tmp_root, 0775, true)) {
        return false;
    }

    $sql_path = $tmp_root . DIRECTORY_SEPARATOR . 'database_dump.sql';
    if (file_put_contents($sql_path, $sql_dump) === false) {
        remove_directory($tmp_root);
        return false;
    }

    if (is_file($zip_path)) {
        @unlink($zip_path);
    }

    $source = escapeshellarg($sql_path);
    $dest = escapeshellarg($zip_path);
    $command = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"Compress-Archive -Path {$source} -DestinationPath {$dest} -Force\"";
    exec($command, $output, $exit_code);
    remove_directory($tmp_root);

    return $exit_code === 0 && is_file($zip_path) && filesize($zip_path) > 0;
}

function remove_directory($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            remove_directory($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
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
