<?php
require_once __DIR__ . '/_draft_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    student_draft_json(['success' => false, 'message' => 'Method not allowed'], 405);
}

student_draft_ensure_schema($conn);

$studentUsername = isset($_GET['student_username']) ? mb_substr(trim((string) $_GET['student_username']), 0, 50) : '';
$draftKey = isset($_GET['draft_key']) ? mb_substr(trim((string) $_GET['draft_key']), 0, 120) : '';

student_draft_validate_student($conn, $studentUsername);
student_draft_validate_key($draftKey);

$stmt = $conn->prepare("
    SELECT id, scholarship_id, application_id, draft_data, file_metadata, updated_at
    FROM application_drafts
    WHERE student_username = ? AND draft_key = ?
    LIMIT 1
");
if (!$stmt) {
    student_draft_json(['success' => false, 'message' => 'Database connection failed: 無法讀取草稿'], 500);
}

$stmt->bind_param('ss', $studentUsername, $draftKey);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    student_draft_json(['success' => true, 'draft' => null]);
}

$draftData = json_decode((string) $row['draft_data'], true);
$fileMetadata = json_decode((string) ($row['file_metadata'] ?? '{}'), true);

student_draft_json([
    'success' => true,
    'draft' => [
        'data' => is_array($draftData) ? $draftData : [],
        'files' => is_array($fileMetadata) ? $fileMetadata : [],
        'savedAt' => $row['updated_at'],
        'scholarship_id' => $row['scholarship_id'] !== null ? (int) $row['scholarship_id'] : null,
        'application_id' => $row['application_id'] !== null ? (int) $row['application_id'] : null,
    ],
]);

