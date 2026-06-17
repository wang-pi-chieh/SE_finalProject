<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password']) || !isset($data['real_name']) || !isset($data['role'])) {
    echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
    exit;
}

$username = $data['username'];
$password = $data['password'];
$real_name = $data['real_name'];
$email = $data['email'] ?? '';
$role = $data['role'];

// Basic Validation
if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => '使用者名稱至少需 3 個字元']);
    exit;
}

try {
    // Check if username exists
    $checkStmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => '此使用者名稱已被使用']);
        exit;
    }

    // Insert user
    $insertStmt = $conn->prepare("INSERT INTO users (username, password, real_name, email, role) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("sssss", $username, $password, $real_name, $email, $role);

    if ($insertStmt->execute()) {
        $new_id = $conn->insert_id;

        // Insert into specific role tables
        if ($role === 'student' || $role === '學生') {
            $stmt = $conn->prepare("INSERT INTO students (username) VALUES (?)");
            $stmt->bind_param("s", $username);
            $stmt->execute();
        } elseif ($role === 'teacher' || $role === '老師') {
            $stmt = $conn->prepare("INSERT INTO teachers (username) VALUES (?)");
            $stmt->bind_param("s", $username);
            $stmt->execute();
        } elseif ($role === 'scholarship_unit' || $role === '獎助單位') {
            // Use real_name as initial unit_name
            $stmt = $conn->prepare("INSERT INTO scholarship_units (username, unit_name) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $real_name);
            $stmt->execute();
        } elseif ($role === 'system_admin' || $role === '系統管理員') {
            $stmt = $conn->prepare("INSERT INTO system_admins (username) VALUES (?)");
            $stmt->bind_param("s", $username);
            $stmt->execute();
        }

        // Log Action
        require_once 'log_utils.php';
        $creator = isset($data['creator']) ? $data['creator'] : 'System Admin'; // Capture creator
        logAction($creator, '新增使用者', "新增帳號: $username ($role), 姓名: $real_name");

        echo json_encode(['success' => true, 'message' => '使用者建立成功', 'id' => $new_id]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '建立失敗: ' . $e->getMessage()]);
}

$conn->close();
?>
