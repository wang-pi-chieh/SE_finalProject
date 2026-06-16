<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

$student = trim($_GET['student_username'] ?? '');
if ($student === '') {
    echo json_encode(['success' => false, 'message' => '缺少學生帳號'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("SELECT academic_year, semester, avg_score, gpa, class_rank, class_size FROM grades WHERE student_username = ? ORDER BY academic_year ASC, semester ASC");
$stmt->bind_param('s', $student);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;
$stmt->close();

$latest = end($rows) ?: [];
$rankPercent = null;
if (!empty($latest) && intval($latest['class_size']) > 0) {
    $rankPercent = round(intval($latest['class_rank']) / intval($latest['class_size']) * 100, 1);
}
$summary = [
    'latest_avg_score' => $latest['avg_score'] ?? null,
    'latest_gpa' => $latest['gpa'] ?? null,
    'latest_rank_percent' => $rankPercent
];

echo json_encode(['success' => true, 'data' => $rows, 'summary' => $summary], JSON_UNESCAPED_UNICODE);
$conn->close();
