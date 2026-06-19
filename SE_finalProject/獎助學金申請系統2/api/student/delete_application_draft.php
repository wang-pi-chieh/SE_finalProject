<?php
require_once __DIR__ . '/_draft_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    student_draft_json(['success' => false, 'message' => 'Method not allowed'], 405);
}

student_draft_ensure_schema($conn);
$input = student_draft_read_json();

$studentUsername = student_draft_string($input, 'student_username', 50);
$draftKey = student_draft_string($input, 'draft_key', 120);

student_draft_validate_student($conn, $studentUsername);
student_draft_validate_key($draftKey);

$stmt = $conn->prepare("DELETE FROM application_drafts WHERE student_username = ? AND draft_key = ?");
if (!$stmt) {
    student_draft_json(['success' => false, 'message' => 'Database connection failed: 無法刪除草稿'], 500);
}

$stmt->bind_param('ss', $studentUsername, $draftKey);
if (!$stmt->execute()) {
    $message = mb_substr($stmt->error ?: '草稿刪除失敗', 0, 255);
    $stmt->close();
    student_draft_json(['success' => false, 'message' => 'Database connection failed: ' . $message], 500);
}

$stmt->close();
student_draft_json(['success' => true, 'message' => '已清除網站暫存']);

