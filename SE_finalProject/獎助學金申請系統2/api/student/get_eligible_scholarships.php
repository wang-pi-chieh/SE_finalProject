<?php
// api/student/get_eligible_scholarships.php
header('Content-Type: application/json; charset=utf-8');
require '../db_connect.php';
require 'matching_utils.php';

$username = trim($_GET['student_username'] ?? '');
$limit = isset($_GET['limit']) ? max(1, min(20, (int) $_GET['limit'])) : 6;

if ($username === '') {
    echo json_encode(['success' => false, 'message' => 'Missing student_username']);
    exit;
}

if (!wu_table_exists($conn, 'scholarship_eligibility_rules')) {
    echo json_encode([
        'success' => false,
        'message' => '請先執行 migrations/005_wu_notification_matching.sql',
        'data' => [],
    ]);
    exit;
}

$context = wu_get_student_context($conn, $username);
if (!$context) {
    echo json_encode(['success' => false, 'message' => '找不到學生資料', 'data' => []]);
    exit;
}

$data = wu_get_eligible_scholarships($conn, $username, $limit);

echo json_encode([
    'success' => true,
    'data' => $data,
    'meta' => [
        'student_username' => $username,
        'department' => $context['student']['department'] ?? null,
        'latest_grade' => $context['latest_grade'],
        'total_matched' => count($data),
    ],
]);

$conn->close();
