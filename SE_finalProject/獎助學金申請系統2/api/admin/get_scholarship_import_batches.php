<?php
require_once __DIR__ . '/_scholarship_import_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    chen_json_response(false, 'Invalid method', [], 405);
}

chen_ensure_import_schema($conn);
chen_require_admin($conn);

$limit = (int) ($_GET['limit'] ?? 8);
if ($limit < 1 || $limit > 50) {
    $limit = 8;
}

$stmt = $conn->prepare(
    'SELECT id, original_filename, uploaded_by, status, total_rows, valid_rows, error_rows, confirmed_at, created_at
     FROM scholarship_import_batches
     ORDER BY created_at DESC, id DESC
     LIMIT ?'
);
if (!$stmt) {
    chen_json_response(false, '讀取匯入紀錄失敗：' . $conn->error, [], 500);
}

$stmt->bind_param('i', $limit);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

chen_json_response(true, '匯入紀錄已載入', [
    'data' => $rows,
]);
?>
