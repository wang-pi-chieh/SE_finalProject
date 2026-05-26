<?php
// api/get_department_students.php
header('Content-Type: application/json');
require 'db_connect.php';

$teacher_username = $_GET['teacher_username'] ?? '';

if (empty($teacher_username)) {
    echo json_encode(["success" => false, "message" => "Missing teacher_username"]);
    exit;
}

// 1. Get Teacher's Department
$stmt = $conn->prepare("SELECT department FROM teachers WHERE username = ?");
$stmt->bind_param("s", $teacher_username);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    echo json_encode(["success" => false, "message" => "Teacher not found"]);
    exit;
}

$department = $teacher['department'];

if (empty($department)) {
    echo json_encode(["success" => false, "message" => "Teacher has no department assigned", "data" => []]);
    exit;
}

// 2. Get Students in that Department
// We join with users to get real name, email, etc.
$sql = "SELECT u.real_name, u.username, u.email, s.grade_level, s.class_name 
        FROM students s
        JOIN users u ON s.username = u.username
        WHERE s.department = ?
        ORDER BY s.grade_level, s.class_name, u.username";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode([
    "success" => true,
    "department" => $department,
    "data" => $students
]);

$conn->close();
?>