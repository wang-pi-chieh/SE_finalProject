<?php
// api/forgot_password.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';
require_once 'auth_password.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($username) || empty($email) || empty($new_password)) {
    echo json_encode(["success" => false, "message" => "缺少必要欄位"]);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(["success" => false, "message" => "新密碼長度至少需 6 碼"]);
    exit;
}

// 確認學號 + Email 是否匹配
$stmt = $conn->prepare("SELECT username FROM users WHERE username = ? AND email = ? LIMIT 1");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "學號與 Email 不相符，請確認後再試"]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

try {
    auth_password_ensure_column($conn);
} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "密碼欄位初始化失敗"]);
    exit;
}

$new_password_hash = auth_password_hash($new_password);

// 更新密碼雜湊
$update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$update->bind_param("ss", $new_password_hash, $username);

if ($update->execute()) {
    echo json_encode(["success" => true, "message" => "密碼重設成功"]);
} else {
    echo json_encode(["success" => false, "message" => "資料庫錯誤：" . $conn->error]);
}

$update->close();
$conn->close();


