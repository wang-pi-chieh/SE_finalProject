<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
session_start();

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username'])) {
    echo json_encode(['success' => false, 'message' => '缺少 Username']);
    exit;
}

$targetUsername = $data['username'];
$currentUsername = $_SESSION['username'] ?? '';

try {
    // Prevent deleting self
    if ($targetUsername === $currentUsername) {
        echo json_encode(['success' => false, 'message' => '您不能刪除自己的帳號']);
        exit;
    }

    // Check if user exists
    $userStmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $userStmt->bind_param("s", $targetUsername);
    $userStmt->execute();
    if ($userStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => '找不到該使用者']);
        exit;
    }

    // Delete user
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $deleteStmt->bind_param("s", $targetUsername);

    if ($deleteStmt->execute()) {
        require_once 'log_utils.php';
        $operator = isset($data['operator']) ? $data['operator'] : ($currentUsername ?: 'System Admin');
        logAction($operator, '刪除使用者', "刪除帳號: $targetUsername");

        echo json_encode(['success' => true, 'message' => '使用者已刪除']);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '刪除失敗: ' . $e->getMessage()]);
}

$conn->close();
?>