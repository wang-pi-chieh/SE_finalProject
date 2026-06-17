<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once __DIR__ . '/admin/_admin_ops_common.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = isset($data['username']) ? trim($data['username']) : '';
$id = isset($data['id']) ? (int) $data['id'] : 0;

if ($username === '' || $id <= 0) {
    echo json_encode(['success' => false, 'message' => '缺少通知編號或使用者'], JSON_UNESCAPED_UNICODE);
    exit;
}

admin_ops_ensure_issue_schema($conn);

$stmt = $conn->prepare("UPDATE issue_report_notifications SET is_read = 1 WHERE id = ? AND recipient_username = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '更新通知失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("is", $id, $username);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
?>
