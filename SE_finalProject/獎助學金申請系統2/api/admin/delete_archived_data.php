<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_archive_schema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admin_ops_json(['success' => false, 'message' => '僅支援 POST。'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$archive_id = isset($input['id']) ? (int) $input['id'] : 0;
if ($archive_id <= 0) {
    admin_ops_json(['success' => false, 'message' => '缺少封存紀錄 ID。'], 400);
}

$stmt = $conn->prepare("
    SELECT id, archive_name, source_table, archive_path, downloaded_at, original_deleted_at
    FROM data_archives
    WHERE id = ?
    LIMIT 1
");
if (!$stmt) {
    admin_ops_json(['success' => false, 'message' => '讀取封存紀錄失敗。'], 500);
}
$stmt->bind_param("i", $archive_id);
$stmt->execute();
$result = $stmt->get_result();
$archive = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$archive || empty($archive['archive_path'])) {
    admin_ops_json(['success' => false, 'message' => '找不到封存檔案紀錄。'], 404);
}

if (!empty($archive['original_deleted_at'])) {
    admin_ops_json(['success' => false, 'message' => '這筆封存的原資料已經移除。'], 400);
}

if (empty($archive['downloaded_at'])) {
    admin_ops_json(['success' => false, 'message' => '請先下載封存檔，再移除原資料。'], 400);
}

$file_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $archive['archive_path']);
if (!is_file($file_path)) {
    admin_ops_json(['success' => false, 'message' => '封存檔案不存在，不能確認要刪除的原資料。'], 404);
}

$payload = read_archive_payload($file_path, $archive);
$archive_type = isset($payload['archive_type']) ? $payload['archive_type'] : infer_archive_type($payload);
$operator = admin_ops_actor();

$conn->begin_transaction();

try {
    $deleted_count = delete_records_by_archive_type($conn, $archive_type, $payload['records']);

    $stmt = $conn->prepare("
        UPDATE data_archives
        SET original_deleted_at = CURRENT_TIMESTAMP,
            original_deleted_by = ?,
            original_deleted_count = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        throw new Exception('無法更新封存刪除狀態。');
    }
    $stmt->bind_param("sii", $operator, $deleted_count, $archive_id);
    if (!$stmt->execute()) {
        throw new Exception('更新封存刪除狀態失敗。');
    }
    $stmt->close();

    $conn->commit();
    logAction($operator, '移除封存原資料', "移除封存 #{$archive_id}: {$archive['archive_name']}，來源 {$archive['source_table']}，共刪除 {$deleted_count} 筆");

    admin_ops_json([
        'success' => true,
        'message' => '原資料已從資料庫移除。',
        'data' => [
            'id' => $archive_id,
            'deleted_count' => $deleted_count,
            'deleted_by' => $operator
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    admin_ops_json(['success' => false, 'message' => $e->getMessage()], 500);
}

function infer_archive_type($payload)
{
    $source = isset($payload['source_table']) ? $payload['source_table'] : '';
    if ($source === 'issue_reports') return 'resolved_issue_reports';
    if ($source === 'applications') return 'applications_by_year';
    if ($source === 'scholarships') return 'expired_scholarships';
    if ($source === 'homepage_announcements') return 'old_announcements';
    if ($source === 'users,students') return 'students_over_years';
    return '';
}

function read_archive_payload($file_path, $archive)
{
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($extension === 'json') {
        $payload = json_decode(file_get_contents($file_path), true);
        if (!is_array($payload) || !isset($payload['records']) || !is_array($payload['records'])) {
            admin_ops_json(['success' => false, 'message' => '封存檔案格式不正確。'], 500);
        }
        return $payload;
    }

    $records = read_csv_records($file_path);
    if (count($records) === 0) {
        admin_ops_json(['success' => false, 'message' => '封存檔案沒有可刪除資料。'], 400);
    }

    return [
        'source_table' => isset($archive['source_table']) ? $archive['source_table'] : '',
        'records' => $records
    ];
}

function read_csv_records($file_path)
{
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        admin_ops_json(['success' => false, 'message' => '無法讀取 CSV 封存檔。'], 500);
    }

    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        return [];
    }

    if (isset($headers[0])) {
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
    }

    $records = [];
    while (($row = fgetcsv($handle)) !== false) {
        $record = [];
        foreach ($headers as $index => $header) {
            $record[$header] = isset($row[$index]) ? $row[$index] : '';
        }
        $records[] = $record;
    }
    fclose($handle);
    return $records;
}

function delete_records_by_archive_type($conn, $archive_type, $records)
{
    switch ($archive_type) {
        case 'students_over_years':
            return delete_students($conn, values_from_records($records, 'username'), count($records));
        case 'applications_by_year':
            return delete_applications($conn, ids_from_records($records));
        case 'expired_scholarships':
            return delete_scholarships($conn, ids_from_records($records));
        case 'old_announcements':
            return delete_simple_ids($conn, 'homepage_announcements', ids_from_records($records));
        case 'resolved_issue_reports':
            return delete_issue_reports($conn, ids_from_records($records));
        default:
            throw new Exception('不支援此封存類型的原資料移除。');
    }
}

function ids_from_records($records)
{
    $ids = [];
    foreach ($records as $record) {
        if (isset($record['id'])) {
            $ids[] = (int) $record['id'];
        }
    }
    return array_values(array_unique(array_filter($ids, function ($id) {
        return $id > 0;
    })));
}

function values_from_records($records, $key)
{
    $values = [];
    foreach ($records as $record) {
        if (isset($record[$key]) && trim((string) $record[$key]) !== '') {
            $values[] = (string) $record[$key];
        }
    }
    return array_values(array_unique($values));
}

function delete_issue_reports($conn, $ids)
{
    if (count($ids) === 0) return 0;
    delete_where_in($conn, 'issue_report_notifications', 'issue_report_id', $ids, 'i');
    return delete_where_in($conn, 'issue_reports', 'id', $ids, 'i');
}

function delete_applications($conn, $ids)
{
    if (count($ids) === 0) return 0;
    delete_where_in($conn, 'student_notifications', 'related_application_id', $ids, 'i');
    delete_where_in($conn, 'reference_letters', 'application_id', $ids, 'i');
    delete_where_in($conn, 'review_records', 'application_id', $ids, 'i');
    return delete_where_in($conn, 'applications', 'id', $ids, 'i');
}

function delete_scholarships($conn, $ids)
{
    if (count($ids) === 0) return 0;
    delete_where_in($conn, 'student_notifications', 'related_scholarship_id', $ids, 'i');
    delete_where_in($conn, 'scholarship_eligibility_rules', 'scholarship_id', $ids, 'i');
    return delete_where_in($conn, 'scholarships', 'id', $ids, 'i');
}

function delete_students($conn, $usernames, $record_count)
{
    if (count($usernames) === 0) return 0;
    delete_where_in($conn, 'student_notifications', 'student_username', $usernames, 's');
    delete_where_in($conn, 'applications', 'student_username', $usernames, 's');
    delete_where_in($conn, 'students', 'username', $usernames, 's');
    delete_where_in($conn, 'users', 'username', $usernames, 's');
    return $record_count;
}

function delete_simple_ids($conn, $table, $ids)
{
    if (count($ids) === 0) return 0;
    return delete_where_in($conn, $table, 'id', $ids, 'i');
}

function delete_where_in($conn, $table, $column, $values, $type)
{
    if (!admin_ops_table_exists($conn, $table) || count($values) === 0) {
        return 0;
    }

    $allowed_tables = [
        'issue_report_notifications',
        'issue_reports',
        'student_notifications',
        'reference_letters',
        'review_records',
        'applications',
        'scholarship_eligibility_rules',
        'scholarships',
        'students',
        'users',
        'homepage_announcements'
    ];
    $allowed_columns = [
        'issue_report_id',
        'id',
        'related_application_id',
        'application_id',
        'related_scholarship_id',
        'scholarship_id',
        'student_username',
        'username'
    ];

    if (!in_array($table, $allowed_tables, true) || !in_array($column, $allowed_columns, true)) {
        throw new Exception('不允許的刪除目標。');
    }

    $placeholders = implode(',', array_fill(0, count($values), '?'));
    $stmt = $conn->prepare("DELETE FROM `{$table}` WHERE `{$column}` IN ({$placeholders})");
    if (!$stmt) {
        throw new Exception("建立刪除 {$table} 語句失敗。");
    }

    $types = str_repeat($type, count($values));
    $params = array_merge([$types], $values);
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("刪除 {$table} 失敗：{$error}");
    }

    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}
?>
