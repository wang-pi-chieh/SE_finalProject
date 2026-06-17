<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

$teacher = trim($_GET['teacher_username'] ?? '');
if ($teacher === '') {
    echo json_encode(['success' => false, 'message' => '缺少導師帳號'], JSON_UNESCAPED_UNICODE);
    exit;
}

$assignmentStmt = $conn->prepare("SELECT ma.department, ma.parity_rule FROM mentor_assignments ma WHERE ma.teacher_username = ? LIMIT 1");
$assignmentStmt->bind_param('s', $teacher);
$assignmentStmt->execute();
$assignment = $assignmentStmt->get_result()->fetch_assoc();
$assignmentStmt->close();

if (!$assignment) {
    $teacherStmt = $conn->prepare("SELECT department FROM teachers WHERE username = ? LIMIT 1");
    $teacherStmt->bind_param('s', $teacher);
    $teacherStmt->execute();
    $teacherRow = $teacherStmt->get_result()->fetch_assoc();
    $teacherStmt->close();
    $assignment = ['department' => $teacherRow['department'] ?? '', 'parity_rule' => 'all'];
}

if (empty($assignment['department'])) {
    echo json_encode(['success' => true, 'assignment' => $assignment, 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$whereParity = '';
if ($assignment['parity_rule'] === 'odd') {
    $whereParity = " AND CAST(RIGHT(s.username, 1) AS UNSIGNED) % 2 = 1";
} elseif ($assignment['parity_rule'] === 'even') {
    $whereParity = " AND CAST(RIGHT(s.username, 1) AS UNSIGNED) % 2 = 0";
}

$sql = "SELECT u.real_name, u.username, u.email, u.phone, s.department, s.grade_level, s.class_name
        FROM students s
        JOIN users u ON s.username = u.username
        WHERE s.department = ? $whereParity
        ORDER BY s.grade_level, s.class_name, u.username";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $assignment['department']);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

echo json_encode(['success' => true, 'assignment' => $assignment, 'data' => $data], JSON_UNESCAPED_UNICODE);
$stmt->close();
$conn->close();
