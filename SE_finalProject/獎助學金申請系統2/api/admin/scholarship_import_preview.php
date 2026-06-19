<?php
require_once __DIR__ . '/_scholarship_import_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chen_json_response(false, 'Invalid method', [], 405);
}

chen_ensure_import_schema($conn);
$uploadedBy = chen_require_admin($conn);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    chen_json_response(false, '請選擇可讀取的 CSV 檔案', [], 400);
}

$file = $_FILES['file'];
$extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
if ($extension !== 'csv') {
    chen_json_response(false, '僅支援 CSV 檔案', [], 400);
}

[$headers, $rawRows] = chen_read_csv_rows((string) $file['tmp_name']);
if (empty($rawRows)) {
    chen_json_response(false, 'CSV 沒有可匯入的資料列', [], 400);
}

$parsed = chen_parse_scholarship_csv($conn, $headers, $rawRows);
$importData = json_encode($parsed['rows'], JSON_UNESCAPED_UNICODE);
$errorRows = array_values(array_filter($parsed['rows'], static fn($row) => !$row['valid']));
$errorReport = json_encode($errorRows, JSON_UNESCAPED_UNICODE);
$status = $parsed['valid_rows'] > 0 ? 'uploaded' : 'failed';

$stmt = $conn->prepare(
    'INSERT INTO scholarship_import_batches
     (original_filename, uploaded_by, status, total_rows, valid_rows, error_rows, import_data, error_report)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    chen_json_response(false, '建立匯入批次失敗：' . $conn->error, [], 500);
}

$originalName = (string) $file['name'];
$stmt->bind_param(
    'sssiiiss',
    $originalName,
    $uploadedBy,
    $status,
    $parsed['total_rows'],
    $parsed['valid_rows'],
    $parsed['error_rows'],
    $importData,
    $errorReport
);

if (!$stmt->execute()) {
    chen_json_response(false, '儲存匯入預覽失敗：' . $stmt->error, [], 500);
}

$batchId = $conn->insert_id;
$stmt->close();

chen_write_system_log(
    $conn,
    $uploadedBy,
    '預覽獎學金匯入',
    "批次 #{$batchId}，有效 {$parsed['valid_rows']} 筆，錯誤 {$parsed['error_rows']} 筆"
);

chen_json_response(true, $parsed['error_rows'] > 0 ? 'CSV 已解析，請修正錯誤列或只確認有效資料' : 'CSV 已解析，可確認匯入', [
    'data' => [
        'batch_id' => $batchId,
        'total_rows' => $parsed['total_rows'],
        'valid_rows' => $parsed['valid_rows'],
        'error_rows' => $parsed['error_rows'],
        'rows' => array_slice($parsed['rows'], 0, 30),
        'truncated' => count($parsed['rows']) > 30,
    ],
]);
?>
