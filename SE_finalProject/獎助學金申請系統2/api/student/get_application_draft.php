<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

$student = trim($_GET['student_username'] ?? '');
$scholarshipId = intval($_GET['scholarship_id'] ?? 0);
if ($student === '' || $scholarshipId <= 0) {
    echo json_encode(['success' => false, 'message' => '缺少學生帳號或獎學金 ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("SELECT id, student_username, scholarship_id, draft_payload, status, updated_at FROM application_drafts WHERE student_username = ? AND scholarship_id = ? AND status = 'draft' LIMIT 1");
$stmt->bind_param('si', $student, $scholarshipId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if ($row) {
    $row['draft_payload'] = json_decode($row['draft_payload'], true) ?: [];
}

echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
$stmt->close();
$conn->close();
