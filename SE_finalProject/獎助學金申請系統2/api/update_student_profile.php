<?php
// api/update_student_profile.php
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$username = $data['username'] ?? '';
$real_name = $data['real_name'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$department = $data['department'] ?? '';
$grade_level = $data['grade_level'] ?? '';
$class_name = $data['class_name'] ?? '';
$gender = $data['gender'] ?? '';
$address = $data['address'] ?? '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Missing username"]);
    exit;
}

// 1. 更新主表 users
$sqlUser = "UPDATE users SET real_name = ?, email = ?, phone = ? WHERE username = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("ssss", $real_name, $email, $phone, $username);

if (!$stmtUser->execute()) {
    echo json_encode(["success" => false, "message" => "更新基本資料失敗: " . $stmtUser->error]);
    exit;
}
$stmtUser->close();

// 2. 更新/插入子表 students
// MySQL 的 INSERT ... ON DUPLICATE KEY UPDATE 語法
$sqlStudent = "
    INSERT INTO students (username, department, grade_level, class_name, gender, address)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    department = VALUES(department),
    grade_level = VALUES(grade_level),
    class_name = VALUES(class_name),
    gender = VALUES(gender),
    address = VALUES(address)
";

$stmtStudent = $conn->prepare($sqlStudent);
$stmtStudent->bind_param("ssssss", $username, $department, $grade_level, $class_name, $gender, $address);

if ($stmtStudent->execute()) {
    echo json_encode(["success" => true, "message" => "資料儲存成功！"]);
} else {
    echo json_encode(["success" => false, "message" => "更新學籍資料失敗: " . $stmtStudent->error]);
}

$stmtStudent->close();
$conn->close();
?>