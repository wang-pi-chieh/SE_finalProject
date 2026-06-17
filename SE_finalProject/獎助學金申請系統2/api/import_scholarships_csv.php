<?php
// api/import_scholarships_csv.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '請選擇可讀取的 CSV 檔案'], JSON_UNESCAPED_UNICODE);
    exit;
}

function ensureReviewCompletedColumn($conn)
{
    $sql = "SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'scholarships'
              AND COLUMN_NAME = 'review_completed'";
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;

    if ((int) ($row['total'] ?? 0) > 0) {
        return true;
    }

    return $conn->query("ALTER TABLE scholarships ADD COLUMN review_completed TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否審核完成' AFTER is_active") === true;
}

if (!ensureReviewCompletedColumn($conn)) {
    echo json_encode(['success' => false, 'message' => '無法建立審核完成欄位：' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['csv_file'];
$extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
if ($extension !== 'csv') {
    echo json_encode(['success' => false, 'message' => '僅支援 .csv 檔案'], JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizeCsvHeader($value)
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
    $value = trim($value);
    $value = strtolower($value);
    return str_replace([' ', '-', '_'], '', $value);
}

function csvValue($row, $headerMap, $aliases)
{
    foreach ($aliases as $alias) {
        $key = normalizeCsvHeader($alias);
        if (isset($headerMap[$key])) {
            $index = $headerMap[$key];
            return trim((string) ($row[$index] ?? ''));
        }
    }
    return '';
}

function truthyCsvValue($value, $default)
{
    $value = strtolower(trim((string) $value));
    if ($value === '') return $default;
    return in_array($value, ['1', 'true', 'yes', 'y', '是', '啟用', '完成'], true) ? 1 : 0;
}

function validDateOrEmpty($value)
{
    $value = trim((string) $value);
    if ($value === '') return '';
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}

$content = file_get_contents($file['tmp_name']);
if ($content === false || trim($content) === '') {
    echo json_encode(['success' => false, 'message' => 'CSV 檔案內容為空'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (function_exists('mb_check_encoding') && !mb_check_encoding($content, 'UTF-8')) {
    $content = mb_convert_encoding($content, 'UTF-8', 'CP950,BIG-5,UTF-8');
}

$handle = fopen('php://temp', 'r+');
fwrite($handle, $content);
rewind($handle);

$headers = fgetcsv($handle);
if (!$headers || count($headers) === 0) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'CSV 缺少標題列'], JSON_UNESCAPED_UNICODE);
    exit;
}

$headerMap = [];
foreach ($headers as $index => $header) {
    $headerMap[normalizeCsvHeader($header)] = $index;
}

$insert = $conn->prepare("
    INSERT INTO scholarships
        (name, description, amount, quota, application_start_date, application_end_date, provider_username, is_active, review_completed)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$insert) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$unitStmt = $conn->prepare("SELECT username FROM scholarship_units WHERE username = ? LIMIT 1");
$imported = 0;
$errors = [];
$line = 1;

while (($row = fgetcsv($handle)) !== false) {
    $line++;

    if (count(array_filter($row, fn($value) => trim((string) $value) !== '')) === 0) {
        continue;
    }

    $name = csvValue($row, $headerMap, ['name', '獎學金名稱', '名稱']);
    $amount = csvValue($row, $headerMap, ['amount', '金額', '獎助金額']);
    $quotaText = csvValue($row, $headerMap, ['quota', '名額']);
    $deadline = csvValue($row, $headerMap, ['deadline', 'application_end_date', '截止日期', '申請截止日期']);
    $startDate = csvValue($row, $headerMap, ['application_start_date', 'start_date', '申請開始日期']);
    $provider = csvValue($row, $headerMap, ['provider_username', 'provider', '負責單位', '獎助單位']);
    $description = csvValue($row, $headerMap, ['description', '詳細說明', '申請條件', '說明']);
    $isActiveRaw = csvValue($row, $headerMap, ['is_active', '啟用', '啟用狀態']);
    $reviewCompletedRaw = csvValue($row, $headerMap, ['review_completed', '審核完成']);

    if ($name === '' || $amount === '' || $deadline === '') {
        $errors[] = "第 {$line} 列：缺少獎學金名稱、金額或截止日期";
        continue;
    }

    $deadline = validDateOrEmpty($deadline);
    if ($deadline === '') {
        $errors[] = "第 {$line} 列：截止日期需為 YYYY-MM-DD";
        continue;
    }

    $startDate = validDateOrEmpty($startDate);
    if ($startDate === '') {
        $startDate = date('Y-m-d');
    }

    $quota = $quotaText === '' ? 0 : (int) $quotaText;
    $provider = $provider !== '' ? $provider : 'admin';

    $unitStmt->bind_param("s", $provider);
    $unitStmt->execute();
    if ($unitStmt->get_result()->num_rows === 0) {
        $errors[] = "第 {$line} 列：找不到獎助單位 {$provider}";
        continue;
    }

    $isActive = truthyCsvValue($isActiveRaw, 1);
    $reviewCompleted = truthyCsvValue($reviewCompletedRaw, 0);

    $insert->bind_param(
        "sssisssii",
        $name,
        $description,
        $amount,
        $quota,
        $startDate,
        $deadline,
        $provider,
        $isActive,
        $reviewCompleted
    );

    if ($insert->execute()) {
        $imported++;
    } else {
        $errors[] = "第 {$line} 列：新增失敗 " . $insert->error;
    }
}

fclose($handle);
$unitStmt->close();
$insert->close();

if ($imported > 0) {
    require_once 'log_utils.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $actor = $_SESSION['username'] ?? 'System Admin';
    logAction($actor, '匯入獎學金CSV', '匯入獎學金項目 ' . $imported . ' 筆');
}

echo json_encode([
    'success' => $imported > 0,
    'imported_count' => $imported,
    'errors' => $errors,
    'message' => $imported > 0 ? 'CSV 匯入完成' : '沒有任何資料被匯入'
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
