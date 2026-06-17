<?php
// api/forgot_password.php
header('Content-Type: application/json');
require 'db_connect.php';

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

// 更新密碼
$update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$update->bind_param("ss", $new_password, $username);

if ($update->execute()) {
    echo json_encode(["success" => true, "message" => "密碼重設成功"]);
} else {
    echo json_encode(["success" => false, "message" => "資料庫錯誤：" . $conn->error]);
}

$update->close();
$conn->close();


