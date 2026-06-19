<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_review_award_common.php';

$providerUsername = trim((string) ($_GET['provider_username'] ?? ''));
$scholarshipId = isset($_GET['scholarship_id']) ? (int) $_GET['scholarship_id'] : 0;

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
    reviewer_award_json([
        'success' => true,
        'data' => $rows,
        'count' => count($rows),
    ]);
} catch (Throwable $e) {
    reviewer_award_json(['success' => false, 'message' => $e->getMessage()], 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
