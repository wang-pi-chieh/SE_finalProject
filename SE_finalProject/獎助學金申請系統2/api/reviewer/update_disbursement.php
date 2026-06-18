<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_disbursement_common.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    reviewer_disbursement_json(['success' => false, 'message' => '輸入格式錯誤'], 400);
}

$id = (int) ($input['id'] ?? 0);
$status = trim((string) ($input['status'] ?? ''));
$providerUsername = trim((string) ($input['provider_username'] ?? ''));
$note = trim((string) ($input['note'] ?? ''));
$allowedStatuses = ['pending', 'paid', 'failed'];

if ($id <= 0) {
    reviewer_disbursement_json(['success' => false, 'message' => '缺少撥款紀錄 ID'], 400);
}
if (!in_array($status, $allowedStatuses, true)) {
    reviewer_disbursement_json(['success' => false, 'message' => '撥款狀態不正確'], 400);
}
if ($providerUsername === '') {
    reviewer_disbursement_json(['success' => false, 'message' => '缺少 provider_username'], 400);
}

try {
    $actor = reviewer_disbursement_actor($conn, $providerUsername);
    if (!$actor) {
        reviewer_disbursement_json(['success' => false, 'message' => '找不到使用者'], 404);
    }

    reviewer_disbursement_ensure_table($conn);
    reviewer_disbursement_sync_approved($conn, $actor);

    $conn->begin_transaction();

    $current = reviewer_disbursement_fetch($conn, $id, $actor);
    if (!$current) {
        throw new Exception('找不到可更新的撥款紀錄');
    }

    $handledBy = (string) $actor['username'];
    $handledAtSql = $status === 'pending' ? 'NULL' : 'NOW()';
    $safeNote = $note !== '' ? $note : null;
    $stmt = $conn->prepare(
        "UPDATE award_disbursements
         SET status = ?, handled_by = ?, handled_at = {$handledAtSql}, note = ?
         WHERE id = ?"
    );
    if (!$stmt) {
        throw new Exception('準備更新撥款狀態失敗：' . $conn->error);
    }
    $stmt->bind_param('sssi', $status, $handledBy, $safeNote, $id);
    if (!$stmt->execute()) {
        throw new Exception('更新撥款狀態失敗：' . $stmt->error);
    }

    $updated = reviewer_disbursement_fetch($conn, $id, $actor);
    if (!$updated) {
        throw new Exception('更新後讀取撥款紀錄失敗');
    }

    $notification = reviewer_disbursement_notify_student($conn, $updated);
    $conn->commit();

    reviewer_disbursement_json([
        'success' => true,
        'message' => '撥款狀態已更新',
        'data' => $updated,
        'notification' => $notification,
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    reviewer_disbursement_json(['success' => false, 'message' => $e->getMessage()], 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
