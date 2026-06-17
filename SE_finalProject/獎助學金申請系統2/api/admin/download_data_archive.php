<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_archive_schema($conn);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo '缺少封存紀錄 ID。';
    exit;
}

$stmt = $conn->prepare("SELECT archive_name, archive_path FROM data_archives WHERE id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo '讀取封存紀錄失敗。';
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$archive = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$archive || empty($archive['archive_path'])) {
    http_response_code(404);
    echo '找不到封存檔案紀錄。';
    exit;
}

$file_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $archive['archive_path']);
if (!is_file($file_path)) {
    http_response_code(404);
    echo '封存檔案不存在。';
    exit;
}

$download_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $archive['archive_name']) . '.csv';
$operator = admin_ops_actor();
$update_stmt = $conn->prepare("UPDATE data_archives SET downloaded_at = CURRENT_TIMESTAMP, downloaded_by = ? WHERE id = ?");
if ($update_stmt) {
    $update_stmt->bind_param("si", $operator, $id);
    $update_stmt->execute();
    $update_stmt->close();
}

logAction($operator, '下載資料封存檔', "下載封存 #{$id}: {$archive['archive_name']}，檔名 {$download_name}");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
if ($extension === 'json') {
    $payload = json_decode(file_get_contents($file_path), true);
    $records = is_array($payload) && isset($payload['records']) && is_array($payload['records']) ? $payload['records'] : [];
    $csv = build_csv($records);
    header('Content-Length: ' . strlen($csv));
    echo $csv;
} else {
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
}
$conn->close();
exit;

function build_csv($records)
{
    if (count($records) === 0) {
        return "\xEF\xBB\xBF";
    }

    $columns = [];
    foreach ($records as $record) {
        foreach (array_keys($record) as $key) {
            if (!in_array($key, $columns, true)) {
                $columns[] = $key;
            }
        }
    }

    $lines = [csv_row($columns)];
    foreach ($records as $record) {
        $row = [];
        foreach ($columns as $column) {
            $value = isset($record[$column]) ? $record[$column] : '';
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $row[] = $value;
        }
        $lines[] = csv_row($row);
    }

    return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
}

function csv_row($values)
{
    $escaped = [];
    foreach ($values as $value) {
        $text = (string) $value;
        $text = str_replace('"', '""', $text);
        $escaped[] = '"' . $text . '"';
    }
    return implode(',', $escaped);
}
?>
