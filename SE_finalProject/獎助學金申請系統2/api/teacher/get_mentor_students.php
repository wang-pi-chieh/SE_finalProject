<?php
// api/teacher/get_mentor_students.php
header('Content-Type: application/json');
require '../db_connect.php';

$teacher_username = $_GET['teacher_username'] ?? '';

if (empty($teacher_username)) {
    echo json_encode(["success" => false, "message" => "Missing teacher_username"]);
    exit;
}

// 1. Get Teacher's Department and Mentor Type
$stmt = $conn->prepare("SELECT department, mentor_type FROM teachers WHERE username = ?");
$stmt->bind_param("s", $teacher_username);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    echo json_encode(["success" => false, "message" => "Teacher not found"]);
    exit;
}

$department = $teacher['department'];
$mentor_type = isset($teacher['mentor_type']) ? (int)$teacher['mentor_type'] : 0;

if (empty($department)) {
    echo json_encode(["success" => false, "message" => "Teacher has no department assigned", "data" => []]);
    exit;
}

// 2. Get Students in that Department
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
    $student_id = $row['username'];
    // 判斷奇偶數規則：取學號的最後一個數字
    $last_char = substr($student_id, -1);
    
    // 如果學號最後一碼不是數字，預設不特別過濾(或可依需求調整)
    $is_numeric = is_numeric($last_char);
    $last_digit = $is_numeric ? intval($last_char) : -1;

    $is_even = ($last_digit !== -1 && $last_digit % 2 === 0);
    $is_odd = ($last_digit !== -1 && $last_digit % 2 !== 0);

    // 過濾條件 (0: 偶數, 1: 奇數)
    if ($mentor_type === 1 && !$is_odd) {
        continue;
    }
    if ($mentor_type === 0 && !$is_even) {
        continue;
    }
    
    $students[] = $row;
}

echo json_encode([
    "success" => true,
    "department" => $department,
    "mentor_type" => $mentor_type,
    "data" => $students
]);

$conn->close();
?>
