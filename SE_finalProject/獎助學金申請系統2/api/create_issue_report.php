<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/admin/_admin_ops_common.php';

$data = json_decode(file_get_contents('php://input'), true);

$title = isset($data['title']) ? trim($data['title']) : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$reporter_username = isset($data['reporter_username']) ? trim($data['reporter_username']) : '';
$reporter_role = isset($data['reporter_role']) ? trim($data['reporter_role']) : '';
$contact_email = isset($data['contact_email']) ? trim($data['contact_email']) : '';
$contact_phone = isset($data['contact_phone']) ? trim($data['contact_phone']) : '';

if ($title === '' || $description === '') {
    echo json_encode(['success' => false, 'message' => '請輸入問題標題與描述'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($contact_email !== '' && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '聯絡 Email 格式不正確'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'db_connect.php';

admin_ops_ensure_issue_schema($conn);

$reporter = $reporter_username !== '' ? $reporter_username : 'anonymous';

$stmt = $conn->prepare("
    INSERT INTO issue_reports
        (reporter_username, reporter_role, title, description, contact_email, contact_phone, status)
    VALUES
        (?, ?, ?, ?, ?, ?, 'open')
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '建立回報失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("ssssss", $reporter, $reporter_role, $title, $description, $contact_email, $contact_phone);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '送出問題回報失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

$report_id = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'message' => '問題回報已送出',
    'id' => $report_id
], JSON_UNESCAPED_UNICODE);
?>
