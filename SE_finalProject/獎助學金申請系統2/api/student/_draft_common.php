<?php
// Shared helpers for student application draft autosave APIs.
require_once __DIR__ . '/../db_connect.php';

function student_draft_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function student_draft_read_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        student_draft_json(['success' => false, 'message' => 'Input invalid: JSON 格式錯誤'], 400);
    }
    return $data;
}

function student_draft_require_owner(mysqli $conn, string $studentUsername): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionUsername = trim((string) ($_SESSION['username'] ?? ''));
    $sessionRole = trim((string) ($_SESSION['role'] ?? ''));

    if ($sessionUsername === '') {
        student_draft_json(['success' => false, 'message' => 'Permission denied: 請先登入學生帳號'], 403);
    }

    if ($sessionUsername === $studentUsername) {
        return;
    }

    if (in_array($sessionRole, ['system_admin', '系統管理員', '系管'], true)) {
        return;
    }

    $stmt = $conn->prepare('SELECT role FROM users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        student_draft_json(['success' => false, 'message' => 'Database connection failed: 無法檢查使用者權限'], 500);
    }

    $stmt->bind_param('s', $sessionUsername);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $role = (string) ($user['role'] ?? '');
    if (in_array($role, ['system_admin', '系統管理員', '系管'], true)) {
        return;
    }

    student_draft_json(['success' => false, 'message' => 'Permission denied: 只能存取自己的申請草稿'], 403);
}

function student_draft_ensure_schema(mysqli $conn): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS application_drafts (
          id int(11) NOT NULL AUTO_INCREMENT,
          student_username varchar(50) NOT NULL,
          draft_key varchar(120) NOT NULL,
          scholarship_id int(11) DEFAULT NULL,
          application_id int(11) DEFAULT NULL,
          draft_data longtext NOT NULL,
          file_metadata longtext DEFAULT NULL,
          last_error varchar(255) DEFAULT NULL,
          created_at datetime NOT NULL DEFAULT current_timestamp(),
          updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (id),
          UNIQUE KEY uniq_application_draft_user_key (student_username, draft_key),
          KEY idx_application_drafts_student_updated (student_username, updated_at),
          KEY idx_application_drafts_application (application_id),
          KEY idx_application_drafts_scholarship (scholarship_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    if (!$conn->query($sql)) {
        student_draft_json(['success' => false, 'message' => 'Database connection failed: 無法建立草稿資料表'], 500);
    }
}

function student_draft_string(array $data, string $key, int $maxLength = 120): string
{
    $value = isset($data[$key]) ? trim((string) $data[$key]) : '';
    return mb_substr($value, 0, $maxLength);
}

function student_draft_optional_int(array $data, string $key): ?int
{
    if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
        return null;
    }
    $value = (int) $data[$key];
    return $value > 0 ? $value : null;
}

function student_draft_validate_student(mysqli $conn, string $username): void
{
    if ($username === '') {
        student_draft_json(['success' => false, 'message' => 'Input invalid: 缺少學生帳號'], 400);
    }

    student_draft_require_owner($conn, $username);

    $stmt = $conn->prepare("SELECT username, role FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) {
        student_draft_json(['success' => false, 'message' => 'Database connection failed: 無法檢查學生帳號'], 500);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        student_draft_json(['success' => false, 'message' => 'Permission denied: 找不到學生帳號'], 403);
    }

    $role = (string) ($user['role'] ?? '');
    if ($role !== 'student' && $role !== '學生') {
        student_draft_json(['success' => false, 'message' => 'Permission denied: 只有學生可以保存申請草稿'], 403);
    }
}

function student_draft_validate_key(string $draftKey): void
{
    if ($draftKey === '') {
        student_draft_json(['success' => false, 'message' => 'Input invalid: 缺少草稿鍵值'], 400);
    }
}
