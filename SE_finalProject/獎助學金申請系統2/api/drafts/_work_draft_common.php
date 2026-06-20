<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../reviewer/_review_award_common.php';

function work_draft_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function work_draft_read_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        work_draft_json(['success' => false, 'message' => 'Input invalid: JSON 格式錯誤'], 400);
    }
    return $data;
}

function work_draft_ensure_schema(mysqli $conn): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS work_drafts (
            id int(11) NOT NULL AUTO_INCREMENT,
            actor_username varchar(50) NOT NULL,
            draft_type varchar(80) NOT NULL,
            draft_key varchar(160) NOT NULL,
            draft_data longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT current_timestamp(),
            updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id),
            UNIQUE KEY uniq_work_draft_actor_type_key (actor_username, draft_type, draft_key),
            KEY idx_work_drafts_actor_updated (actor_username, updated_at),
            KEY idx_work_drafts_type_key (draft_type, draft_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    if (!$conn->query($sql)) {
        work_draft_json(['success' => false, 'message' => 'Database connection failed: 無法建立暫存資料表'], 500);
    }
}

function work_draft_string(array $data, string $key, int $maxLength): string
{
    $value = isset($data[$key]) ? trim((string) $data[$key]) : '';
    return mb_substr($value, 0, $maxLength);
}

function work_draft_application_id(array $data, bool $required = true): int
{
    $context = isset($data['context']) && is_array($data['context']) ? $data['context'] : [];
    $applicationId = isset($context['application_id']) ? (int) $context['application_id'] : 0;
    if ($applicationId <= 0 && isset($data['application_id'])) {
        $applicationId = (int) $data['application_id'];
    }
    if ($required && $applicationId <= 0) {
        work_draft_json(['success' => false, 'message' => 'Input invalid: 缺少申請編號'], 400);
    }
    return $applicationId;
}

function work_draft_actor(mysqli $conn, string $username): ?array
{
    if ($username === '') {
        return null;
    }

    $stmt = $conn->prepare('SELECT username, real_name, role FROM users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        work_draft_json(['success' => false, 'message' => 'Database connection failed: 無法檢查使用者'], 500);
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $actor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $actor ?: null;
}

function work_draft_validate_access(mysqli $conn, string $draftType, string $actorUsername, int $applicationId): void
{
    $actor = work_draft_actor($conn, $actorUsername);
    if (!$actor) {
        work_draft_json(['success' => false, 'message' => 'Permission denied: 找不到使用者'], 403);
    }

    $role = (string) ($actor['role'] ?? '');

    if ($draftType === 'admin_form') {
        if (!in_array($role, ['system_admin', '系統管理員', '系管', 'admin'], true)) {
            work_draft_json(['success' => false, 'message' => 'Permission denied: 只有系統管理員可以暫存管理表單'], 403);
        }
        return;
    }

    if ($draftType === 'teacher_tool') {
        if (!in_array($role, ['teacher', '老師'], true)) {
            work_draft_json(['success' => false, 'message' => 'Permission denied: 只有老師可以暫存老師工具內容'], 403);
        }
        return;
    }

    if ($draftType === 'teacher_recommendation') {
        if ($applicationId <= 0) {
            work_draft_json(['success' => false, 'message' => 'Input invalid: 缺少申請編號'], 400);
        }

        if (!in_array($role, ['teacher', '老師'], true)) {
            work_draft_json(['success' => false, 'message' => 'Permission denied: 只有老師可以暫存推薦信'], 403);
        }

        $teacherRealName = (string) ($actor['real_name'] ?? '');
        $stmt = $conn->prepare("
            SELECT id
            FROM applications
            WHERE id = ?
              AND (
                  referrer_username = ?
                  OR (referrer_name = ? AND referrer_name IS NOT NULL AND referrer_name != '')
              )
            LIMIT 1
        ");
        if (!$stmt) {
            work_draft_json(['success' => false, 'message' => 'Database connection failed: 無法檢查推薦信權限'], 500);
        }
        $stmt->bind_param('iss', $applicationId, $actorUsername, $teacherRealName);
        $stmt->execute();
        $allowed = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$allowed) {
            work_draft_json(['success' => false, 'message' => 'Permission denied: 無法暫存不屬於自己的推薦信'], 403);
        }
        return;
    }

    if ($draftType === 'reviewer_review') {
        if ($applicationId <= 0) {
            work_draft_json(['success' => false, 'message' => 'Input invalid: 缺少申請編號'], 400);
        }

        if (!in_array($role, ['獎助單位', 'scholarship_unit', 'reviewer', 'system_admin', '系統管理員', '系管', 'admin'], true)) {
            work_draft_json(['success' => false, 'message' => 'Permission denied: 只有獎助單位可以暫存審查意見'], 403);
        }

        reviewer_award_ensure_schema($conn);
        $application = reviewer_award_fetch_application($conn, $applicationId, $actor);
        if (!$application) {
            work_draft_json(['success' => false, 'message' => 'Permission denied: 無法暫存無權審查的申請'], 403);
        }
        return;
    }

    work_draft_json(['success' => false, 'message' => 'Input invalid: 不支援的暫存類型'], 400);
}
?>
