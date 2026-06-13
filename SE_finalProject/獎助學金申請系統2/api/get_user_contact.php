<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if ($username === '') {
    echo json_encode(['success' => false, 'message' => '缺少使用者帳號'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("SELECT username, real_name, role, email, phone FROM users WHERE username = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '讀取使用者聯絡資料失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();
$conn->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => '找不到使用者'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => true, 'data' => $user], JSON_UNESCAPED_UNICODE);
?>
