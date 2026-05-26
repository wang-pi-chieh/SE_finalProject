<?php
// api/change_password.php
header('Content-Type: application/json');
require 'db_connect.php';

// 僅允許 POST + JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$old_password = $input['old_password'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($username) || empty($old_password) || empty($new_password)) {
    echo json_encode(["success" => false, "message" => "缺少必要欄位"]);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(["success" => false, "message" => "新密碼長度至少需 6 碼"]);
    exit;
}

// 目前系統登入是以明文密碼比對，因此這裡也沿用相同做法
$stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "找不到使用者"]);
    $stmt->close();
    $conn->close();
    exit;
}

$row = $result->fetch_assoc();

if ($row['password'] !== $old_password) {
    echo json_encode(["success" => false, "message" => "目前密碼不正確"]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

// 更新成新密碼
$update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$update->bind_param("ss", $new_password, $username);

if ($update->execute()) {
    echo json_encode(["success" => true, "message" => "密碼更新成功"]);
} else {
    echo json_encode(["success" => false, "message" => "資料庫錯誤：" . $conn->error]);
}

$update->close();
$conn->close();


