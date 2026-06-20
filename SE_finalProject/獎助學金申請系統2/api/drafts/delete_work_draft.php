<?php
require_once __DIR__ . '/_work_draft_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    work_draft_json(['success' => false, 'message' => 'Method not allowed'], 405);
}

work_draft_ensure_schema($conn);
$input = work_draft_read_json();

$actorUsername = work_draft_string($input, 'actor_username', 50);
$draftType = work_draft_string($input, 'draft_type', 80);
$draftKey = work_draft_string($input, 'draft_key', 160);
$applicationId = work_draft_application_id($input, false);

if ($actorUsername === '' || $draftType === '' || $draftKey === '') {
    work_draft_json(['success' => false, 'message' => 'Input invalid: 缺少暫存識別資料'], 400);
}

work_draft_validate_access($conn, $draftType, $actorUsername, $applicationId);

$stmt = $conn->prepare("
    DELETE FROM work_drafts
    WHERE actor_username = ? AND draft_type = ? AND draft_key = ?
");
if (!$stmt) {
    work_draft_json(['success' => false, 'message' => 'Database connection failed: 無法清除暫存'], 500);
}

$stmt->bind_param('sss', $actorUsername, $draftType, $draftKey);
$stmt->execute();
$stmt->close();

work_draft_json(['success' => true, 'message' => '已清除網站暫存']);
?>
