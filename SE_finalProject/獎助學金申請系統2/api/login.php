<?php
// api/login.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';
require_once 'auth_password.php';

function login_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    login_json_response(["success" => false, "message" => "No data received"], 400);
}

$username = trim((string) ($data['username'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($username === '' || $password === '') {
    login_json_response(["success" => false, "message" => "請輸入帳號與密碼"], 400);
}

$selectColumns = "username, role, real_name, phone, email, avatar_url, password";
$user = null;

// 先用 username 主鍵查詢，避免每次登入都走 username/email OR 掃描。
$stmt = $conn->prepare("SELECT {$selectColumns} FROM users WHERE username = ? LIMIT 1");
if (!$stmt) {
    login_json_response(["success" => false, "message" => "Database connection failed"], 500);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// username 沒找到時才退到 email 登入，保留原本可用 Email 登入的行為。
if (!$user) {
    $stmt = $conn->prepare("SELECT {$selectColumns} FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        login_json_response(["success" => false, "message" => "Database connection failed"], 500);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}

if ($user && auth_password_verify($password, (string) $user['password'])) {
    auth_password_upgrade_if_needed($conn, $user['username'], $password, (string) $user['password']);
    unset($user['password']);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    $conn->close();
    login_json_response(["success" => true, "message" => "登入成功", "user" => $user]);
}

$conn->close();
login_json_response(["success" => false, "message" => "帳號或密碼錯誤"], 401);
?>
