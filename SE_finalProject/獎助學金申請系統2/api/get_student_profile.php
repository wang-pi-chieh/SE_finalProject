<?php
// api/get_student_profile.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

$username = $_GET['username'] ?? '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Missing username"], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "
    SELECT users.username, users.role, users.real_name, users.email, users.phone, users.avatar_url,
           students.department, students.grade_level, students.class_name, students.gender, students.address
    FROM users
    LEFT JOIN students ON users.username = students.username
    WHERE users.username = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "找不到使用者資料"], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>
