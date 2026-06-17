<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once __DIR__ . '/admin/_admin_ops_common.php';

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
if ($username === '') {
    echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

admin_ops_ensure_issue_schema($conn);

$stmt = $conn->prepare("
    SELECT n.id, n.issue_report_id, n.title, n.message, n.is_read, n.created_at
    FROM issue_report_notifications n
    INNER JOIN issue_reports r
        ON r.id = n.issue_report_id
        AND r.reporter_username = n.recipient_username
    WHERE n.recipient_username = ? AND n.is_read = 0
    ORDER BY n.created_at DESC
    LIMIT 10
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '讀取問題回報通知失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'data' => $notifications], JSON_UNESCAPED_UNICODE);
?>
