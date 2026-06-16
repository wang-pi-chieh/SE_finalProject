<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

$teacher = trim($_GET['teacher_username'] ?? '');
$scholarshipId = intval($_GET['scholarship_id'] ?? 0);
if ($teacher === '' || $scholarshipId <= 0) {
    echo json_encode(['success' => false, 'message' => '缺少導師帳號或獎學金 ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ruleStmt = $conn->prepare("SELECT s.name, s.description, r.department_filter, r.min_avg_score, r.max_rank_percent FROM scholarships s LEFT JOIN mentor_scholarship_rules r ON r.scholarship_id = s.id WHERE s.id = ?");
$ruleStmt->bind_param('i', $scholarshipId);
$ruleStmt->execute();
$rule = $ruleStmt->get_result()->fetch_assoc();
$ruleStmt->close();
if (!$rule) {
    echo json_encode(['success' => false, 'message' => '找不到獎學金'], JSON_UNESCAPED_UNICODE);
    exit;
}

$assignmentStmt = $conn->prepare("SELECT department, parity_rule FROM mentor_assignments WHERE teacher_username = ? LIMIT 1");
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

$whereParity = '';
if ($assignment['parity_rule'] === 'odd') $whereParity = " AND CAST(RIGHT(st.username, 1) AS UNSIGNED) % 2 = 1";
if ($assignment['parity_rule'] === 'even') $whereParity = " AND CAST(RIGHT(st.username, 1) AS UNSIGNED) % 2 = 0";

$sql = "SELECT u.username, u.real_name, st.department, g.avg_score, g.class_rank, g.class_size
        FROM students st
        JOIN users u ON st.username = u.username
        LEFT JOIN grades g ON g.student_username = st.username
        WHERE st.department = ? $whereParity
        GROUP BY u.username, u.real_name, st.department, g.avg_score, g.class_rank, g.class_size
        ORDER BY u.username";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $assignment['department']);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($student = $result->fetch_assoc()) {
    $eligible = true;
    $reasons = [];
    if (!empty($rule['department_filter'])) {
        $allowed = array_map('trim', explode(',', $rule['department_filter']));
        if (!in_array($student['department'], $allowed, true)) {
            $eligible = false;
            $reasons[] = '系所不符合';
        } else {
            $reasons[] = '系所符合';
        }
    }
    if ($rule['min_avg_score'] !== null) {
        if (floatval($student['avg_score']) < floatval($rule['min_avg_score'])) {
            $eligible = false;
            $reasons[] = '平均成績未達門檻';
        } else {
            $reasons[] = '平均成績符合';
        }
    }
    if ($rule['max_rank_percent'] !== null && intval($student['class_size']) > 0) {
        $rankPercent = intval($student['class_rank']) / intval($student['class_size']);
        if ($rankPercent > floatval($rule['max_rank_percent'])) {
            $eligible = false;
            $reasons[] = '班排未達門檻';
        } else {
            $reasons[] = '班排符合';
        }
    }
    if ($eligible) $data[] = $student + ['reasons' => $reasons ?: ['符合導師名下學生與基本條件']];
}
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
$conn->close();
