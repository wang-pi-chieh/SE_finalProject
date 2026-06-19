<?php
require_once __DIR__ . '/_draft_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    student_draft_json(['success' => false, 'message' => 'Method not allowed'], 405);
}

student_draft_ensure_schema($conn);
$input = student_draft_read_json();

$studentUsername = student_draft_string($input, 'student_username', 50);
$draftKey = student_draft_string($input, 'draft_key', 120);
$scholarshipId = student_draft_optional_int($input, 'scholarship_id');
$applicationId = student_draft_optional_int($input, 'application_id');
$draft = $input['draft'] ?? null;

student_draft_validate_student($conn, $studentUsername);
student_draft_validate_key($draftKey);

if (!is_array($draft)) {
    student_draft_json(['success' => false, 'message' => 'Input invalid: 草稿內容格式錯誤'], 400);
}

$draftData = json_encode($draft['data'] ?? [], JSON_UNESCAPED_UNICODE);
$fileMetadata = json_encode($draft['files'] ?? [], JSON_UNESCAPED_UNICODE);
if ($draftData === false || $fileMetadata === false) {
    student_draft_json(['success' => false, 'message' => 'Input invalid: 草稿內容無法序列化'], 400);
}

$sql = "
    INSERT INTO application_drafts
        (student_username, draft_key, scholarship_id, application_id, draft_data, file_metadata, last_error)
    VALUES (?, ?, ?, ?, ?, ?, NULL)
    ON DUPLICATE KEY UPDATE
        scholarship_id = VALUES(scholarship_id),
        application_id = VALUES(application_id),
        draft_data = VALUES(draft_data),
        file_metadata = VALUES(file_metadata),
        last_error = NULL,
        updated_at = CURRENT_TIMESTAMP
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    student_draft_json(['success' => false, 'message' => 'Database connection failed: 無法準備草稿保存'], 500);
}

$stmt->bind_param('ssiiss', $studentUsername, $draftKey, $scholarshipId, $applicationId, $draftData, $fileMetadata);
if (!$stmt->execute()) {
    $message = mb_substr($stmt->error ?: '草稿保存失敗', 0, 255);
    $stmt->close();
    student_draft_json(['success' => false, 'message' => 'Database connection failed: ' . $message], 500);
}

$stmt->close();
student_draft_json([
    'success' => true,
    'message' => '已保存網站暫存',
    'saved_at' => date('c'),
]);

