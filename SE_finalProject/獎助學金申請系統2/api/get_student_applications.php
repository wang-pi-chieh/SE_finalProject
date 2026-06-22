<?php
// api/get_student_applications.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

$username = $_GET['student_username'] ?? '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Missing student_username"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Join applications with scholarships to get name and amount
$sql = "SELECT a.id, a.scholarship_id, a.review_comment, a.application_date, a.academic_year, a.semester, a.status, s.name as scholarship_name, s.amount 
        FROM applications a
        LEFT JOIN scholarships s ON a.scholarship_id = s.id
        WHERE a.student_username = ?
        ORDER BY a.application_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$applications = [];
while ($row = $result->fetch_assoc()) {
    // Return raw integer status (0, 1, 2, 3)
    // The frontend now handles integer parsing.
    $applications[] = $row;
}

echo json_encode(["success" => true, "data" => $applications], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
