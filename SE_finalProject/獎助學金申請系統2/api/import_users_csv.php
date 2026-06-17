<?php
// api/import_users_csv.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$rawInput = preg_replace('/^\xEF\xBB\xBF/', '', $rawInput);
$input = json_decode($rawInput, true);
$users = $input['users'] ?? null;
$creator = $input['creator'] ?? 'System Admin';

if (!is_array($users) || count($users) === 0) {
    echo json_encode(['success' => false, 'message' => '沒有可匯入的使用者資料'], JSON_UNESCAPED_UNICODE);
    exit;
}

$validRoles = ['學生', '老師', '獎助單位', '系統管理員'];

function normalizeRoleForImport($role)
{
    $role = trim((string) $role);
    $map = [
        'student' => '學生',
        'teacher' => '老師',
        'scholarship_unit' => '獎助單位',
        'scholarshipunit' => '獎助單位',
        'system_admin' => '系統管理員',
        'systemadmin' => '系統管理員'
    ];
    $key = strtolower($role);
    return $map[$key] ?? $role;
}

function createRoleRecord($conn, $username, $role, $realName)
{
    if ($role === '學生') {
        $stmt = $conn->prepare("INSERT INTO students (username) VALUES (?)");
        $stmt->bind_param("s", $username);
        return $stmt->execute();
    }

    if ($role === '老師') {
        $stmt = $conn->prepare("INSERT INTO teachers (username) VALUES (?)");
        $stmt->bind_param("s", $username);
        return $stmt->execute();
    }

    if ($role === '獎助單位') {
        $stmt = $conn->prepare("INSERT INTO scholarship_units (username, unit_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $realName);
        return $stmt->execute();
    }

    if ($role === '系統管理員') {
        $stmt = $conn->prepare("INSERT INTO system_admins (username) VALUES (?)");
        $stmt->bind_param("s", $username);
        return $stmt->execute();
    }

    return false;
}

$checkStmt = $conn->prepare("SELECT username FROM users WHERE username = ? LIMIT 1");
$insertStmt = $conn->prepare("INSERT INTO users (username, password, real_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");

if (!$checkStmt || !$insertStmt) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$imported = 0;
$errors = [];
$seen = [];

foreach ($users as $index => $user) {
    $line = (int) ($user['line'] ?? ($index + 2));
    $username = trim((string) ($user['username'] ?? ''));
    $password = trim((string) ($user['password'] ?? ''));
    $realName = trim((string) ($user['real_name'] ?? ''));
    $email = trim((string) ($user['email'] ?? ''));
    $phone = trim((string) ($user['phone'] ?? ''));
    $role = normalizeRoleForImport($user['role'] ?? '');

    if (strlen($username) < 3 || $password === '' || $realName === '' || !in_array($role, $validRoles, true)) {
        $errors[] = "第 {$line} 列：必要欄位不完整或身分組不支援";
        continue;
    }

    if (strlen($password) > 20) {
        $errors[] = "第 {$line} 列：密碼不可超過 20 個字元";
        continue;
    }

    if (isset($seen[$username])) {
        $errors[] = "第 {$line} 列：CSV 內帳號重複";
        continue;
    }
    $seen[$username] = true;

    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $errors[] = "第 {$line} 列：帳號 {$username} 已存在";
        continue;
    }

    $conn->begin_transaction();
    try {
        // 目前 login.php 以明文密碼比對，且 users.password 欄位為 varchar(20)，匯入沿用現有登入機制。
        $insertStmt->bind_param("ssssss", $username, $password, $realName, $email, $phone, $role);
        if (!$insertStmt->execute()) {
            throw new Exception($insertStmt->error);
        }

        if (!createRoleRecord($conn, $username, $role, $realName)) {
            throw new Exception('建立角色資料失敗：' . $conn->error);
        }

        $conn->commit();
        $imported++;
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "第 {$line} 列：新增失敗 " . $e->getMessage();
    }
}

if ($imported > 0) {
    require_once 'log_utils.php';
    logAction($creator, '匯入使用者CSV', '匯入使用者 ' . $imported . ' 筆');
}

echo json_encode([
    'success' => $imported > 0,
    'imported_count' => $imported,
    'errors' => $errors,
    'message' => $imported > 0 ? 'CSV 匯入完成' : '沒有任何使用者被匯入'
], JSON_UNESCAPED_UNICODE);

$checkStmt->close();
$insertStmt->close();
$conn->close();
?>
