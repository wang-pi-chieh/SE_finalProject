<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_mentor_common.php';

$teacher = trim($_GET['teacher_username'] ?? '');
$scholarshipId = intval($_GET['scholarship_id'] ?? 0);
if ($teacher === '' || $scholarshipId <= 0) {
    mentor_json_response(['success' => false, 'message' => '缺少導師帳號或獎學金 ID'], 400);
}

mentor_ensure_scholarship_rules_table($conn);

$ruleStmt = $conn->prepare("SELECT s.name, s.description, r.department_filter, r.min_avg_score, r.max_rank_percent FROM scholarships s LEFT JOIN mentor_scholarship_rules r ON r.scholarship_id = s.id WHERE s.id = ?");
if (!$ruleStmt) {
    mentor_json_response(['success' => false, 'message' => '獎學金規則查詢初始化失敗'], 500);
}
$ruleStmt->bind_param('i', $scholarshipId);
$ruleStmt->execute();
$rule = $ruleStmt->get_result()->fetch_assoc();
$ruleStmt->close();
if (!$rule) {
    mentor_json_response(['success' => false, 'message' => '找不到獎學金'], 404);
}

$assignment = mentor_get_assignment($conn, $teacher);

$whereParity = mentor_parity_sql('st.username', $assignment['parity_rule'] ?? 'all');

$sql = "SELECT u.username, u.real_name, st.department, g.avg_score, g.class_rank, g.class_size
        FROM students st
        JOIN users u ON st.username = u.username
        LEFT JOIN (
            SELECT g1.student_username, g1.avg_score, g1.class_rank, g1.class_size
            FROM grades g1
            INNER JOIN (
                SELECT student_username, MAX(CONCAT(LPAD(academic_year, 4, '0'), '-', LPAD(semester, 2, '0'))) AS latest_term
                FROM grades
                GROUP BY student_username
            ) latest_grade ON latest_grade.student_username = g1.student_username
                AND latest_grade.latest_term = CONCAT(LPAD(g1.academic_year, 4, '0'), '-', LPAD(g1.semester, 2, '0'))
        ) g ON g.student_username = st.username
        WHERE st.department = ? $whereParity
        ORDER BY u.username";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    mentor_json_response(['success' => false, 'message' => '符合資格學生查詢初始化失敗'], 500);
}
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
