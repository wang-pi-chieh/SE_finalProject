<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_mentor_common.php';

$teacher = trim($_GET['teacher_username'] ?? '');
if ($teacher === '') {
    mentor_json_response(['success' => false, 'message' => '缺少導師帳號'], 400);
}

$assignment = mentor_get_assignment($conn, $teacher);

if (empty($assignment['department'])) {
    mentor_json_response(['success' => true, 'assignment' => $assignment, 'data' => []]);
}

$whereParity = mentor_parity_sql('s.username', $assignment['parity_rule'] ?? 'all');

    $sql = "SELECT u.real_name, u.username, u.email, u.phone, s.department, s.grade_level, s.class_name
        FROM students s
        JOIN users u ON s.username = u.username
        LEFT JOIN (
            SELECT student_username, COUNT(*) AS grade_count
            FROM grades
            GROUP BY student_username
        ) grade_stats ON grade_stats.student_username = s.username
        WHERE s.department = ? $whereParity
        ORDER BY COALESCE(grade_stats.grade_count, 0) = 0, s.grade_level, s.class_name, u.username";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    mentor_json_response(['success' => false, 'message' => '名下學生查詢初始化失敗'], 500);
}
$stmt->bind_param('s', $assignment['department']);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

echo json_encode(['success' => true, 'assignment' => $assignment, 'data' => $data], JSON_UNESCAPED_UNICODE);
$stmt->close();
$conn->close();
