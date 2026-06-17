<?php
// api/login.php
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// 查詢使用者
// 注意：這裡直接比對明文密碼，因為原本 Node.js 也是這樣寫的。建議未來改用 password_hash()
// 使用 BINARY 讓密碼比對區分大小寫，避免因資料庫預設 Collation 導致不分大小寫
$sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND BINARY password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Start Session explicitly
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    echo json_encode(["success" => true, "message" => "登入成功", "user" => $user]);
} else {
    // 401 Unauthorized
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "帳號或密碼錯誤"]);
}

$stmt->close();
$conn->close();
?>