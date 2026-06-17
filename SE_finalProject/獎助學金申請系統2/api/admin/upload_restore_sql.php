<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_restore_schema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admin_ops_json(['success' => false, 'message' => '僅支援 POST 上傳。'], 405);
}

if (!isset($_FILES['restore_sql']) || !is_uploaded_file($_FILES['restore_sql']['tmp_name'])) {
    admin_ops_json(['success' => false, 'message' => '請選擇要還原的 .sql 檔案。'], 400);
}

$file = $_FILES['restore_sql'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    admin_ops_json(['success' => false, 'message' => 'SQL 檔案上傳失敗。'], 400);
}

$original_name = basename($file['name']);
$extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
if ($extension !== 'sql') {
    admin_ops_json(['success' => false, 'message' => '只允許上傳 .sql 檔案。'], 400);
}

$upload_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'restore_uploads';
if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
    admin_ops_json(['success' => false, 'message' => '無法建立還原檔案存放目錄。'], 500);
}

$safe_original = preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
$stored_name = 'restore_sql_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safe_original;
$target_path = $upload_dir . DIRECTORY_SEPARATOR . $stored_name;
$relative_path = 'restore_uploads/' . $stored_name;
$operator = admin_ops_actor();
$file_size = (int) $file['size'];

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    admin_ops_json(['success' => false, 'message' => '無法儲存 SQL 檔案。'], 500);
}

$conn->begin_transaction();

try {
    $message = 'SQL 檔案已上傳，待管理員至 phpMyAdmin 匯入。';
    $status = 'started';
    $log_stmt = $conn->prepare("INSERT INTO restore_logs (restored_by, status, message) VALUES (?, ?, ?)");
    if (!$log_stmt) {
        throw new Exception('無法建立還原紀錄。');
    }
    $log_stmt->bind_param("sss", $operator, $status, $message);
    if (!$log_stmt->execute()) {
        throw new Exception('無法寫入還原紀錄。');
    }
    $restore_log_id = $conn->insert_id;
    $log_stmt->close();

    $upload_stmt = $conn->prepare("
        INSERT INTO restore_uploads (restore_log_id, original_name, stored_name, stored_path, file_size, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$upload_stmt) {
        throw new Exception('無法建立 SQL 上傳紀錄。');
    }
    $upload_stmt->bind_param("isssis", $restore_log_id, $original_name, $stored_name, $relative_path, $file_size, $operator);
    if (!$upload_stmt->execute()) {
        throw new Exception('無法寫入 SQL 上傳紀錄。');
    }
    $upload_id = $conn->insert_id;
    $upload_stmt->close();

    $conn->commit();
    logAction($operator, '上傳還原 SQL', "上傳還原 SQL #{$upload_id}: {$original_name}，檔案 {$relative_path}");

    admin_ops_json([
        'success' => true,
        'message' => 'SQL 檔案已上傳，請開啟 phpMyAdmin 匯入。',
        'data' => [
            'id' => $upload_id,
            'restore_log_id' => $restore_log_id,
            'original_name' => $original_name,
            'stored_name' => $stored_name,
            'stored_path' => $relative_path,
            'absolute_path' => $target_path,
            'file_size' => $file_size,
            'uploaded_by' => $operator,
            'phpmyadmin_url' => 'http://localhost/phpmyadmin/index.php?route=/database/import&db=se_finalproject'
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    if (is_file($target_path)) {
        unlink($target_path);
    }
    admin_ops_json(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
