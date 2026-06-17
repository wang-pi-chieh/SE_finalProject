<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../admin/_admin_ops_common.php';

admin_ops_ensure_teacher_notification_schema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$teacher = trim($input['teacher_username'] ?? '');
$applicationId = intval($input['application_id'] ?? 0);
$reason = trim($input['reason'] ?? '');
if ($teacher === '' || $applicationId <= 0 || $reason === '') {
    echo json_encode(['success' => false, 'message' => '導師、申請 ID 與退回理由皆為必填'], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->begin_transaction();
try {
    $appStmt = $conn->prepare("SELECT student_username FROM applications WHERE id = ? AND (referrer_username = ? OR referrer_username IS NULL OR referrer_username = '') FOR UPDATE");
    $appStmt->bind_param('is', $applicationId, $teacher);
    $appStmt->execute();
    $app = $appStmt->get_result()->fetch_assoc();
    $appStmt->close();
    if (!$app) throw new Exception('找不到可退回的申請或權限不符');

    $update = $conn->prepare("UPDATE applications SET status = 2, review_comment = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
    $update->bind_param('ssi', $reason, $teacher, $applicationId);
    if (!$update->execute()) throw new Exception($update->error);
    $update->close();

    $record = $conn->prepare("INSERT INTO mentor_return_records (application_id, teacher_username, student_username, reason) VALUES (?, ?, ?, ?)");
    $record->bind_param('isss', $applicationId, $teacher, $app['student_username'], $reason);
    if (!$record->execute()) throw new Exception($record->error);
    $record->close();

    $message = '申請 #' . $applicationId . ' 已退回補件，理由：' . $reason;
    $dedupKey = 'mentor-return:' . $teacher . ':' . $applicationId;
    $notify = $conn->prepare("
        INSERT INTO teacher_notifications
            (teacher_username, type, title, message, related_application_id, dedup_key, is_read)
        VALUES (?, 'return', '已退回學生補件', ?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE
            message = VALUES(message),
            related_application_id = VALUES(related_application_id),
            is_read = 0,
            created_at = CURRENT_TIMESTAMP
    ");
    if (!$notify) {
        throw new Exception('建立老師通知失敗');
    }
    $notify->bind_param('ssis', $teacher, $message, $applicationId, $dedupKey);
    if (!$notify->execute()) {
        throw new Exception($notify->error);
    }
    $notify->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => '已退回學生補件'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
