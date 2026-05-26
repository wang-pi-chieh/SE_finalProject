<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$student = trim($input['student_username'] ?? '');
$scholarshipId = intval($input['scholarship_id'] ?? 0);
$payload = $input['draft_payload'] ?? [];
if ($student === '' || $scholarshipId <= 0) {
    echo json_encode(['success' => false, 'message' => '缺少學生帳號或獎學金 ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
$stmt = $conn->prepare("INSERT INTO application_drafts (student_username, scholarship_id, draft_payload, status) VALUES (?, ?, ?, 'draft')
                        ON DUPLICATE KEY UPDATE draft_payload = VALUES(draft_payload), status = 'draft', updated_at = NOW()");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('sis', $student, $scholarshipId, $json);
$ok = $stmt->execute();
$draftId = $stmt->insert_id;
if ($draftId === 0) {
    $lookup = $conn->prepare("SELECT id FROM application_drafts WHERE student_username = ? AND scholarship_id = ?");
    $lookup->bind_param('si', $student, $scholarshipId);
    $lookup->execute();
    $row = $lookup->get_result()->fetch_assoc();
    $draftId = intval($row['id'] ?? 0);
    $lookup->close();
}

echo json_encode(['success' => $ok, 'message' => $ok ? '草稿已儲存' : $stmt->error, 'draft_id' => $draftId], JSON_UNESCAPED_UNICODE);
$stmt->close();
$conn->close();
