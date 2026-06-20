<?php
require_once __DIR__ . '/_work_draft_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    work_draft_json(['success' => false, 'message' => 'Method not allowed'], 405);
}

work_draft_ensure_schema($conn);

$actorUsername = work_draft_string($_GET, 'actor_username', 50);
$draftType = work_draft_string($_GET, 'draft_type', 80);
$draftKey = work_draft_string($_GET, 'draft_key', 160);
$applicationId = work_draft_application_id($_GET, false);

if ($actorUsername === '' || $draftType === '' || $draftKey === '') {
    work_draft_json(['success' => false, 'message' => 'Input invalid: 缺少暫存識別資料'], 400);
}

work_draft_validate_access($conn, $draftType, $actorUsername, $applicationId);

$stmt = $conn->prepare("
    SELECT draft_data, updated_at
    FROM work_drafts
    WHERE actor_username = ? AND draft_type = ? AND draft_key = ?
    LIMIT 1
");
if (!$stmt) {
    work_draft_json(['success' => false, 'message' => 'Database connection failed: 無法讀取暫存'], 500);
}

$stmt->bind_param('sss', $actorUsername, $draftType, $draftKey);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    work_draft_json(['success' => true, 'draft' => null]);
}

$draft = json_decode((string) $row['draft_data'], true);
if (!is_array($draft)) {
    $draft = ['data' => []];
}
$draft['savedAt'] = $draft['savedAt'] ?? date('c', strtotime((string) $row['updated_at']));

work_draft_json(['success' => true, 'draft' => $draft]);
?>
