<?php
// api/get_student_grades.php
header('Content-Type: application/json');
require 'db_connect.php';

$username = $_GET['student_username'] ?? '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Missing student_username"]);
    exit;
}

// 取得學生的成績紀錄
$sql = "SELECT academic_year, semester, avg_score, gpa, class_rank, class_size 
        FROM grades 
        WHERE student_username = ? 
        ORDER BY academic_year DESC, semester DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}

echo json_encode(["success" => true, "data" => $grades]);

$stmt->close();
$conn->close();
?>