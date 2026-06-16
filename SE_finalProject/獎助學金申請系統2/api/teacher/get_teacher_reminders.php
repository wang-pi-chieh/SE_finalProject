<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

$teacher = trim($_GET['teacher_username'] ?? '');
if ($teacher === '') {
    echo json_encode(['success' => false, 'message' => '缺少導師帳號'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT a.id AS application_id, u.real_name AS student_name, a.student_username, s.name AS scholarship_name, s.application_end_date
        FROM applications a
        JOIN users u ON a.student_username = u.username
        JOIN scholarships s ON a.scholarship_id = s.id
        LEFT JOIN reference_letters rl ON rl.application_id = a.id AND rl.teacher_username = ? AND rl.status = 'submitted'
        WHERE a.recommendation_required = 1
          AND a.referrer_username = ?
          AND rl.id IS NULL
          AND DATEDIFF(s.application_end_date, CURRENT_DATE) = 5
        ORDER BY s.application_end_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $teacher, $teacher);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
$stmt->close();
$conn->close();
