<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../log_utils.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_issue_schema($conn);
admin_ops_ensure_archive_schema($conn);
admin_ops_ensure_teacher_notification_schema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admin_ops_json(['success' => false, 'message' => '僅支援 POST。'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$archive_type = isset($input['archive_type']) ? $input['archive_type'] : 'resolved_issue_reports';
$operator = admin_ops_actor();
$archive_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'archives';
if (!is_dir($archive_dir) && !mkdir($archive_dir, 0775, true)) {
    admin_ops_json(['success' => false, 'message' => '無法建立封存檔案目錄。'], 500);
}

$archive = build_archive_dataset($conn, $archive_type, $input);
$records = $archive['records'];
if (count($records) === 0) {
    admin_ops_json(['success' => false, 'message' => $archive['empty_message']], 400);
}

$archive_name = $archive['name_prefix'];
$stored_name = $archive['name_prefix'] . '_' . date('Ymd_His') . '.csv';
$target_path = $archive_dir . DIRECTORY_SEPARATOR . $stored_name;
$relative_path = 'archives/' . $stored_name;

$csv = build_csv($records);
if (file_put_contents($target_path, $csv) === false) {
    admin_ops_json(['success' => false, 'message' => '建立封存檔案失敗。'], 500);
}

$file_size = filesize($target_path);
if ($file_size === false) {
    $file_size = 0;
}

$conn->begin_transaction();

try {
    $source_table = $archive['source_table'];
    $record_count = count($records);
    $stmt = $conn->prepare("
        INSERT INTO data_archives (archive_name, source_table, record_count, archive_path, file_size, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception('無法建立封存紀錄。');
    }
    $stmt->bind_param("ssisis", $archive_name, $source_table, $record_count, $relative_path, $file_size, $operator);
    if (!$stmt->execute()) {
        throw new Exception('寫入封存紀錄失敗。');
    }
    $archive_id = $conn->insert_id;
    $stmt->close();

    $conn->commit();
    logAction($operator, '建立資料封存', "建立封存 #{$archive_id}: {$archive_name}，來源 {$source_table}，共 {$record_count} 筆，檔案 {$relative_path}");

    admin_ops_json([
        'success' => true,
        'message' => '已建立封存檔。',
        'data' => [
            'id' => $archive_id,
            'archive_name' => $archive_name,
            'source_table' => $source_table,
            'record_count' => $record_count,
            'archive_path' => $relative_path,
            'file_size' => $file_size,
            'created_by' => $operator
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    if (is_file($target_path)) {
        unlink($target_path);
    }
    admin_ops_json(['success' => false, 'message' => $e->getMessage()], 500);
}

function build_archive_dataset($conn, $archive_type, $input)
{
    switch ($archive_type) {
        case 'students_over_years':
            return build_students_archive($conn, $input);
        case 'applications_by_year':
            return build_applications_archive($conn, $input);
        case 'expired_scholarships':
            return build_expired_scholarships_archive($conn);
        case 'old_announcements':
            return build_old_announcements_archive($conn);
        case 'departed_teachers':
            return build_departed_teachers_archive($conn, $input);
        case 'resolved_issue_reports':
        default:
            return build_resolved_issue_reports_archive($conn);
    }
}

function build_csv($records)
{
    if (count($records) === 0) {
        return '';
    }

    $columns = [];
    foreach ($records as $record) {
        foreach (array_keys($record) as $key) {
            if (!in_array($key, $columns, true)) {
                $columns[] = $key;
            }
        }
    }

    $lines = [];
    $lines[] = csv_row($columns);
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

function build_resolved_issue_reports_archive($conn)
{
    $stmt = $conn->prepare("
        SELECT id, reporter_username, reporter_role, title, description, contact_email, contact_phone, status, handled_by, created_at, updated_at
        FROM issue_reports
        WHERE status = 'resolved'
        ORDER BY updated_at DESC, created_at DESC
    ");

    $records = fetch_all_or_fail($stmt, '讀取已解決問題回報失敗。');
    return [
        'name_prefix' => 'issue_reports_resolved',
        'source_table' => 'issue_reports',
        'filter' => ['status' => 'resolved'],
        'records' => $records,
        'empty_message' => '目前沒有已解決的問題回報可以封存。'
    ];
}

function build_students_archive($conn, $input)
{
    $cutoff_year = isset($input['cutoff_year']) ? (int) $input['cutoff_year'] : (int) date('Y') - 1911 - 4;
    $selected = isset($input['selected_usernames']) && is_array($input['selected_usernames']) ? $input['selected_usernames'] : [];
    $selected_map = [];
    foreach ($selected as $username) {
        $selected_map[(string) $username] = true;
    }

    $stmt = $conn->prepare("
        SELECT u.username, u.real_name, u.role, u.phone, u.email,
               s.department, s.gender, s.grade_level, s.class_name, s.address, s.application_history
        FROM users u
        INNER JOIN students s ON s.username = u.username
        WHERE u.role = '學生'
        ORDER BY u.username ASC
    ");
    $students = fetch_all_or_fail($stmt, '讀取學生資料失敗。');

    $records = [];
    foreach ($students as $student) {
        $admission_year = extract_admission_year($student['username']);
        if ($admission_year === null || $admission_year > $cutoff_year) {
            continue;
        }
        if (count($selected_map) > 0 && !isset($selected_map[$student['username']])) {
            continue;
        }
        $student['admission_year'] = $admission_year;
        $student['archive_reason'] = '入學年小於等於 ' . $cutoff_year;
        $records[] = $student;
    }

    return [
        'name_prefix' => 'students_admission_before_' . $cutoff_year,
        'source_table' => 'users,students',
        'filter' => ['admission_year_lte' => $cutoff_year, 'selected_usernames' => array_keys($selected_map)],
        'records' => $records,
        'empty_message' => '沒有符合條件的學生可以封存。'
    ];
}

function build_applications_archive($conn, $input)
{
    $academic_year = isset($input['academic_year']) ? trim((string) $input['academic_year']) : '';
    if ($academic_year === '') {
        admin_ops_json(['success' => false, 'message' => '請輸入要封存的申請學年度。'], 400);
    }

    $stmt = $conn->prepare("
        SELECT a.*, u.real_name AS student_name, s.name AS scholarship_name
        FROM applications a
        LEFT JOIN users u ON u.username = a.student_username
        LEFT JOIN scholarships s ON s.id = a.scholarship_id
        WHERE a.academic_year = ?
        ORDER BY a.application_date DESC, a.id DESC
    ");
    if (!$stmt) {
        admin_ops_json(['success' => false, 'message' => '讀取申請紀錄失敗。'], 500);
    }
    $stmt->bind_param("s", $academic_year);
    $records = fetch_all_or_fail($stmt, '讀取申請紀錄失敗。');

    return [
        'name_prefix' => 'applications_year_' . preg_replace('/[^0-9A-Za-z_-]/', '_', $academic_year),
        'source_table' => 'applications',
        'filter' => ['academic_year' => $academic_year],
        'records' => $records,
        'empty_message' => '該學年度沒有申請紀錄可以封存。'
    ];
}

function build_expired_scholarships_archive($conn)
{
    $stmt = $conn->prepare("
        SELECT s.*, su.unit_name AS provider_name
        FROM scholarships s
        LEFT JOIN scholarship_units su ON su.username = s.provider_username
        WHERE s.is_active = 0 OR (s.application_end_date IS NOT NULL AND s.application_end_date < CURDATE())
        ORDER BY s.application_end_date DESC, s.id DESC
    ");
    $records = fetch_all_or_fail($stmt, '讀取獎學金項目失敗。');
    return [
        'name_prefix' => 'scholarships_expired',
        'source_table' => 'scholarships',
        'filter' => ['is_active' => 0, 'application_end_date_before' => date('Y-m-d')],
        'records' => $records,
        'empty_message' => '目前沒有過期或停用的獎學金項目可以封存。'
    ];
}

function build_old_announcements_archive($conn)
{
    $stmt = $conn->prepare("
        SELECT *
        FROM homepage_announcements
        WHERE display_date IS NOT NULL AND display_date < CURDATE()
        ORDER BY display_date DESC, id DESC
    ");
    $records = fetch_all_or_fail($stmt, '讀取公告失敗。');
    return [
        'name_prefix' => 'homepage_announcements_old',
        'source_table' => 'homepage_announcements',
        'filter' => ['display_date_before' => date('Y-m-d')],
        'records' => $records,
        'empty_message' => '目前沒有過期公告可以封存。'
    ];
}

function build_departed_teachers_archive($conn, $input)
{
    $selected = isset($input['selected_usernames']) && is_array($input['selected_usernames']) ? $input['selected_usernames'] : [];
    $selected = array_values(array_unique(array_filter(array_map(function ($username) {
        return trim((string) $username);
    }, $selected), function ($username) {
        return $username !== '';
    })));

    if (count($selected) === 0) {
        admin_ops_json(['success' => false, 'message' => '請先選擇要封存的離職老師。'], 400);
    }

    $records = [];
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $types = str_repeat('s', count($selected));

    $profile_stmt = $conn->prepare("
        SELECT 'teacher_profile' AS record_type,
               u.username, u.real_name, u.role, u.phone, u.email,
               t.department, t.position,
               NULL AS related_id, NULL AS application_id, NULL AS student_username,
               NULL AS scholarship_id, NULL AS title, NULL AS message,
               NULL AS content, NULL AS file_path, NULL AS status,
               NULL AS created_at
        FROM users u
        INNER JOIN teachers t ON t.username = u.username
        WHERE u.username IN ({$placeholders})
        ORDER BY u.username ASC
    ");
    append_rows($records, $profile_stmt, $types, $selected, '讀取離職老師資料失敗。');

    if (admin_ops_table_exists($conn, 'reference_letters')) {
        $letter_stmt = $conn->prepare("
            SELECT 'reference_letter' AS record_type,
                   rl.teacher_username AS username, u.real_name, u.role, u.phone, u.email,
                   t.department, t.position,
                   rl.id AS related_id, rl.application_id, a.student_username,
                   a.scholarship_id, NULL AS title, NULL AS message,
                   rl.content, rl.file_path, rl.status,
                   rl.filled_at AS created_at
            FROM reference_letters rl
            LEFT JOIN users u ON u.username = rl.teacher_username
            LEFT JOIN teachers t ON t.username = rl.teacher_username
            LEFT JOIN applications a ON a.id = rl.application_id
            WHERE rl.teacher_username IN ({$placeholders})
            ORDER BY rl.teacher_username ASC, rl.id ASC
        ");
        append_rows($records, $letter_stmt, $types, $selected, '讀取老師推薦信資料失敗。');
    }

    if (admin_ops_table_exists($conn, 'teacher_notifications')) {
        $notification_stmt = $conn->prepare("
            SELECT 'teacher_notification' AS record_type,
                   tn.teacher_username AS username, u.real_name, u.role, u.phone, u.email,
                   t.department, t.position,
                   tn.id AS related_id, tn.related_application_id AS application_id,
                   NULL AS student_username, NULL AS scholarship_id,
                   tn.title, tn.message,
                   NULL AS content, NULL AS file_path, NULL AS status,
                   tn.created_at
            FROM teacher_notifications tn
            LEFT JOIN users u ON u.username = tn.teacher_username
            LEFT JOIN teachers t ON t.username = tn.teacher_username
            WHERE tn.teacher_username IN ({$placeholders})
            ORDER BY tn.teacher_username ASC, tn.id ASC
        ");
        append_rows($records, $notification_stmt, $types, $selected, '讀取老師通知資料失敗。');
    }

    if (admin_ops_table_exists($conn, 'applications') && admin_ops_column_exists($conn, 'applications', 'referrer_username')) {
        $application_stmt = $conn->prepare("
            SELECT 'referred_application' AS record_type,
                   a.referrer_username AS username, u.real_name, u.role, u.phone, u.email,
                   t.department, t.position,
                   a.id AS related_id, a.id AS application_id, a.student_username,
                   a.scholarship_id, NULL AS title, NULL AS message,
                   NULL AS content, NULL AS file_path, a.status,
                   a.created_at
            FROM applications a
            LEFT JOIN users u ON u.username = a.referrer_username
            LEFT JOIN teachers t ON t.username = a.referrer_username
            WHERE a.referrer_username IN ({$placeholders})
            ORDER BY a.referrer_username ASC, a.id ASC
        ");
        append_rows($records, $application_stmt, $types, $selected, '讀取老師相關申請資料失敗。');
    }

    return [
        'name_prefix' => 'teachers_departed',
        'source_table' => 'users,teachers',
        'filter' => ['selected_usernames' => $selected],
        'records' => $records,
        'empty_message' => '沒有符合條件的離職老師可以封存。'
    ];
}

function fetch_all_or_fail($stmt, $message)
{
    if (!$stmt || !$stmt->execute()) {
        admin_ops_json(['success' => false, 'message' => $message], 500);
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function append_rows(&$records, $stmt, $types, $params, $message)
{
    if (!$stmt) {
        admin_ops_json(['success' => false, 'message' => $message], 500);
    }

    $bind = array_merge([$types], $params);
    $refs = [];
    foreach ($bind as $key => $value) {
        $refs[$key] = &$bind[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    $rows = fetch_all_or_fail($stmt, $message);
    foreach ($rows as $row) {
        $records[] = $row;
    }
}

function extract_admission_year($username)
{
    if (preg_match('/(\d{3})/', (string) $username, $matches)) {
        return (int) $matches[1];
    }
    return null;
}
?>
