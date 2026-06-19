<?php
require_once __DIR__ . '/_scholarship_import_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    chen_json_response(false, 'Invalid method', [], 405);
}

chen_ensure_import_schema($conn);

$exportedBy = chen_require_admin($conn);
$rows = [];
$sql = "
    SELECT
      s.id,
      s.name,
      s.provider_username,
      su.unit_name,
      s.description,
      s.amount,
      s.quota,
      s.application_start_date,
      s.application_end_date,
      s.is_active,
      s.created_at
    FROM scholarships s
    LEFT JOIN scholarship_units su ON su.username = s.provider_username
    ORDER BY s.created_at DESC, s.id DESC
";

$result = $conn->query($sql);
if (!$result) {
    chen_json_response(false, '匯出查詢失敗：' . $conn->error, [], 500);
}
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

$fileName = 'scholarships_' . date('Ymd_His') . '.csv';
$filters = json_encode(['source' => 'admin/system-settings'], JSON_UNESCAPED_UNICODE);
$stmt = $conn->prepare(
    'INSERT INTO scholarship_export_logs (export_type, exported_by, row_count, file_name, filters)
     VALUES (?, ?, ?, ?, ?)'
);
if ($stmt) {
    $type = 'scholarships_csv';
    $rowCount = count($rows);
    $stmt->bind_param('ssiss', $type, $exportedBy, $rowCount, $fileName, $filters);
    $stmt->execute();
    $stmt->close();
}
chen_write_system_log($conn, $exportedBy, '匯出獎學金 CSV', '匯出 ' . count($rows) . ' 筆獎學金資料');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, [
    'id',
    'name',
    'provider_username',
    'unit_name',
    'description',
    'amount',
    'quota',
    'application_start_date',
    'application_end_date',
    'is_active',
    'created_at',
]);
foreach ($rows as $row) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['provider_username'],
        $row['unit_name'],
        $row['description'],
        $row['amount'],
        $row['quota'],
        $row['application_start_date'],
        $row['application_end_date'],
        $row['is_active'],
        $row['created_at'],
    ]);
}
fclose($output);
exit;
?>
