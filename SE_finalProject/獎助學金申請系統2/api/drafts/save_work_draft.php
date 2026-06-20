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
$draft = $input['draft'] ?? null;

if ($actorUsername === '' || $draftType === '' || $draftKey === '') {
    work_draft_json(['success' => false, 'message' => 'Input invalid: 缺少暫存識別資料'], 400);
}

if (!is_array($draft)) {
    work_draft_json(['success' => false, 'message' => 'Input invalid: 暫存內容格式錯誤'], 400);
}

work_draft_validate_access($conn, $draftType, $actorUsername, $applicationId);

$draftData = json_encode([
    'data' => $draft['data'] ?? [],
    'savedAt' => date('c'),
], JSON_UNESCAPED_UNICODE);

if ($draftData === false) {
    work_draft_json(['success' => false, 'message' => 'Input invalid: 暫存內容無法序列化'], 400);
}

$stmt = $conn->prepare("
    INSERT INTO work_drafts (actor_username, draft_type, draft_key, draft_data)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        draft_data = VALUES(draft_data),
        updated_at = CURRENT_TIMESTAMP
");
if (!$stmt) {
    work_draft_json(['success' => false, 'message' => 'Database connection failed: 無法準備暫存保存'], 500);
}

$stmt->bind_param('ssss', $actorUsername, $draftType, $draftKey, $draftData);
if (!$stmt->execute()) {
    $message = mb_substr($stmt->error ?: '暫存保存失敗', 0, 255);
    $stmt->close();
    work_draft_json(['success' => false, 'message' => 'Database connection failed: ' . $message], 500);
}

$stmt->close();
work_draft_json([
    'success' => true,
    'message' => '已同步網站暫存',
    'saved_at' => date('c'),
]);
?>
