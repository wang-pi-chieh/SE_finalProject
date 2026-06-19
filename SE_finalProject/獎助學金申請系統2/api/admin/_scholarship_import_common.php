<?php
require_once __DIR__ . '/../db_connect.php';

function chen_json_response(bool $success, string $message, array $data = [], int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function chen_require_admin(mysqli $conn): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $username = trim((string) ($_SESSION['username'] ?? ''));
    $role = trim((string) ($_SESSION['role'] ?? ''));

    if ($username === '') {
        chen_json_response(false, 'Permission denied: 請先登入系統管理員帳號', [], 403);
    }

    if (in_array($role, ['system_admin', '系統管理員', '系管'], true)) {
        return $username;
    }

    $stmt = $conn->prepare("
        SELECT u.role, sa.username AS admin_username
        FROM users u
        LEFT JOIN system_admins sa ON sa.username = u.username
        WHERE u.username = ?
        LIMIT 1
    ");
    if (!$stmt) {
        chen_json_response(false, 'Database connection failed: 無法檢查管理員權限', [], 500);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $dbRole = (string) ($user['role'] ?? '');
    $isAdmin = in_array($dbRole, ['system_admin', '系統管理員', '系管'], true) || !empty($user['admin_username']);
    if (!$isAdmin) {
        chen_json_response(false, 'Permission denied: 只有系統管理員可以匯入或匯出獎學金資料', [], 403);
    }

    return $username;
}

function chen_ensure_import_schema(mysqli $conn): void
{
    $batchSql = "
        CREATE TABLE IF NOT EXISTS scholarship_import_batches (
          id int(11) NOT NULL AUTO_INCREMENT,
          original_filename varchar(255) NOT NULL,
          uploaded_by varchar(50) DEFAULT NULL,
          status enum('uploaded','confirmed','failed') NOT NULL DEFAULT 'uploaded',
          total_rows int(11) NOT NULL DEFAULT 0,
          valid_rows int(11) NOT NULL DEFAULT 0,
          error_rows int(11) NOT NULL DEFAULT 0,
          import_data longtext NOT NULL,
          error_report longtext DEFAULT NULL,
          confirmed_at datetime DEFAULT NULL,
          created_at datetime NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (id),
          KEY idx_scholarship_import_batches_status_created (status, created_at),
          KEY idx_scholarship_import_batches_uploader (uploaded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    $exportSql = "
        CREATE TABLE IF NOT EXISTS scholarship_export_logs (
          id int(11) NOT NULL AUTO_INCREMENT,
          export_type varchar(50) NOT NULL DEFAULT 'scholarships_csv',
          exported_by varchar(50) DEFAULT NULL,
          row_count int(11) NOT NULL DEFAULT 0,
          file_name varchar(255) DEFAULT NULL,
          filters longtext DEFAULT NULL,
          created_at datetime NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (id),
          KEY idx_scholarship_export_logs_created (created_at),
          KEY idx_scholarship_export_logs_exporter (exported_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    if (!$conn->query($batchSql)) {
        chen_json_response(false, '建立匯入批次資料表失敗：' . $conn->error, [], 500);
    }

    if (!$conn->query($exportSql)) {
        chen_json_response(false, '建立匯出紀錄資料表失敗：' . $conn->error, [], 500);
    }
}

function chen_clean_header(string $header): string
{
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
    $header = trim($header);
    $header = strtolower($header);
    return str_replace([' ', '-', '＿'], ['_', '_', '_'], $header);
}

function chen_header_aliases(): array
{
    return [
        'name' => ['name', 'scholarship_name', '獎學金名稱', '獎助學金名稱'],
        'provider_username' => ['provider_username', 'provider', 'sponsor_username', 'unit_username', '獎助單位帳號', '發布單位帳號', '負責單位帳號'],
        'description' => ['description', 'desc', '說明', '詳細說明', '申請條件'],
        'amount' => ['amount', '獎助金額', '金額'],
        'quota' => ['quota', '名額'],
        'application_start_date' => ['application_start_date', 'start_date', '開始日期', '申請開始日期'],
        'application_end_date' => ['application_end_date', 'deadline', 'end_date', '截止日期', '申請截止日期'],
        'is_active' => ['is_active', 'active', '啟用', '是否啟用'],
    ];
}

function chen_header_map(array $headers): array
{
    $cleanHeaders = array_map('chen_clean_header', $headers);
    $map = [];

    foreach (chen_header_aliases() as $field => $aliases) {
        foreach ($aliases as $alias) {
            $index = array_search(chen_clean_header($alias), $cleanHeaders, true);
            if ($index !== false) {
                $map[$field] = $index;
                break;
            }
        }
    }

    return $map;
}

function chen_read_csv_rows(string $path): array
{
    $handle = fopen($path, 'r');
    if (!$handle) {
        chen_json_response(false, '無法讀取上傳的 CSV 檔案', [], 400);
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        chen_json_response(false, 'CSV 檔案是空的', [], 400);
    }

    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count(array_filter($row, static fn($value) => trim((string) $value) !== '')) === 0) {
            continue;
        }
        $rows[] = $row;
    }
    fclose($handle);

    return [$headers, $rows];
}

function chen_normalize_date(?string $value, bool $required, string $label, array &$errors): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        if ($required) {
            $errors[] = "{$label}不可空白";
        }
        return null;
    }

    $formats = ['Y-m-d', 'Y/m/d', 'Y.m.d'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        $dateErrors = DateTime::getLastErrors();
        if ($date instanceof DateTime && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))) {
            return $date->format('Y-m-d');
        }
    }

    $errors[] = "{$label}格式需為 YYYY-MM-DD";
    return null;
}

function chen_parse_active(?string $value, array &$errors): int
{
    $value = trim((string) $value);
    if ($value === '') {
        return 1;
    }

    $truthy = ['1', 'true', 'yes', 'y', '啟用', '是'];
    $falsy = ['0', 'false', 'no', 'n', '停用', '否'];
    $normalized = strtolower($value);
    if (in_array($normalized, $truthy, true)) {
        return 1;
    }
    if (in_array($normalized, $falsy, true)) {
        return 0;
    }

    $errors[] = '是否啟用只能填 1/0、啟用/停用或是/否';
    return 1;
}

function chen_provider_exists(mysqli $conn, string $providerUsername): bool
{
    $stmt = $conn->prepare('SELECT username FROM scholarship_units WHERE username = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $providerUsername);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function chen_cell(array $row, array $map, string $field): string
{
    if (!array_key_exists($field, $map)) {
        return '';
    }

    return trim((string) ($row[$map[$field]] ?? ''));
}

function chen_parse_scholarship_csv(mysqli $conn, array $headers, array $rawRows): array
{
    $map = chen_header_map($headers);
    $requiredFields = ['name', 'provider_username', 'amount', 'quota', 'application_end_date'];
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $map)) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        chen_json_response(false, 'CSV 欄位缺少：' . implode(', ', $missing), [
            'required_headers' => $requiredFields,
        ], 400);
    }

    $previewRows = [];
    $validRows = 0;
    $errorRows = 0;

    foreach ($rawRows as $index => $row) {
        $line = $index + 2;
        $errors = [];

        $name = chen_cell($row, $map, 'name');
        $provider = chen_cell($row, $map, 'provider_username');
        $amount = chen_cell($row, $map, 'amount');
        $quotaText = chen_cell($row, $map, 'quota');
        $description = chen_cell($row, $map, 'description');
        $startDate = chen_normalize_date(chen_cell($row, $map, 'application_start_date'), false, '開始日期', $errors);
        $endDate = chen_normalize_date(chen_cell($row, $map, 'application_end_date'), true, '截止日期', $errors);
        $isActive = chen_parse_active(chen_cell($row, $map, 'is_active'), $errors);

        if ($name === '') {
            $errors[] = '獎學金名稱不可空白';
        }
        if ($provider === '') {
            $errors[] = '獎助單位帳號不可空白';
        } elseif (!chen_provider_exists($conn, $provider)) {
            $errors[] = "找不到獎助單位帳號 {$provider}";
        }
        if ($amount === '') {
            $errors[] = '金額不可空白';
        }
        if ($quotaText === '' || filter_var($quotaText, FILTER_VALIDATE_INT) === false || (int) $quotaText < 0) {
            $errors[] = '名額需為 0 以上整數';
        }

        $data = [
            'name' => $name,
            'provider_username' => $provider,
            'description' => $description,
            'amount' => $amount,
            'quota' => max(0, (int) $quotaText),
            'application_start_date' => $startDate ?: date('Y-m-d'),
            'application_end_date' => $endDate,
            'is_active' => $isActive,
        ];

        $valid = empty($errors);
        $valid ? $validRows++ : $errorRows++;

        $previewRows[] = [
            'line' => $line,
            'valid' => $valid,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    return [
        'rows' => $previewRows,
        'total_rows' => count($previewRows),
        'valid_rows' => $validRows,
        'error_rows' => $errorRows,
    ];
}

function chen_write_system_log(mysqli $conn, string $actor, string $action, string $details): void
{
    $stmt = $conn->prepare('INSERT INTO system_logs (user_role, action_type, details) VALUES (?, ?, ?)');
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('sss', $actor, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
