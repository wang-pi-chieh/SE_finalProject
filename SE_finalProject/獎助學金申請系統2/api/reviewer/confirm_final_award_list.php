<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_review_award_common.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    reviewer_award_json(['success' => false, 'message' => '輸入格式錯誤'], 400);
}

$providerUsername = trim((string) ($input['provider_username'] ?? ''));
$scholarshipId = isset($input['scholarship_id']) ? (int) $input['scholarship_id'] : 0;

if ($providerUsername === '') {
    reviewer_award_json(['success' => false, 'message' => '缺少 provider_username'], 400);
}

try {
    reviewer_award_ensure_schema($conn);
    $actor = reviewer_award_actor($conn, $providerUsername);
    if (!$actor) {
        reviewer_award_json(['success' => false, 'message' => '找不到使用者'], 404);
    }

    $rows = reviewer_award_fetch_final_list($conn, $actor, $scholarshipId);
    $conn->begin_transaction();
    $saved = reviewer_award_save_final_list($conn, $rows, $actor);
    $conn->commit();

    reviewer_award_json([
        'success' => true,
        'message' => '最終錄取名單已確認',
        'saved_count' => $saved,
        'data' => $rows,
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    reviewer_award_json(['success' => false, 'message' => $e->getMessage()], 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
