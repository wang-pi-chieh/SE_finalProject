<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$teacher = trim($input['teacher_username'] ?? '');
$applicationId = intval($input['application_id'] ?? 0);
$templateKey = trim($input['template_key'] ?? 'general');
if ($teacher === '' || $applicationId <= 0) {
    echo json_encode(['success' => false, 'message' => '缺少導師帳號或申請 ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT a.id, a.student_username, u.real_name AS student_name, s.name AS scholarship_name, st.department, g.avg_score, g.gpa, g.class_rank, g.class_size, tu.real_name AS teacher_name
        FROM applications a
        JOIN users u ON a.student_username = u.username
        JOIN scholarships s ON a.scholarship_id = s.id
        LEFT JOIN students st ON a.student_username = st.username
        LEFT JOIN users tu ON tu.username = ?
        LEFT JOIN grades g ON g.student_username = a.student_username
        WHERE a.id = ?
        ORDER BY g.academic_year DESC, g.semester DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $teacher, $applicationId);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$app) {
    echo json_encode(['success' => false, 'message' => '找不到申請資料'], JSON_UNESCAPED_UNICODE);
    exit;
}

$templateStmt = $conn->prepare("SELECT title, content FROM recommendation_templates WHERE template_key = ? AND is_active = 1 LIMIT 1");
$templateStmt->bind_param('s', $templateKey);
$templateStmt->execute();
$template = $templateStmt->get_result()->fetch_assoc();
$templateStmt->close();
if (!$template) {
    echo json_encode(['success' => false, 'message' => '找不到推薦信範本'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rankPercent = '';
if (intval($app['class_size'] ?? 0) > 0) {
    $rankPercent = round(intval($app['class_rank']) / intval($app['class_size']) * 100, 1) . '%';
}
$replace = [
    '{student_name}' => $app['student_name'] ?? '',
    '{student_username}' => $app['student_username'] ?? '',
    '{department}' => $app['department'] ?? '',
    '{scholarship_name}' => $app['scholarship_name'] ?? '',
    '{avg_score}' => $app['avg_score'] ?? '',
    '{gpa}' => $app['gpa'] ?? '',
    '{rank_percent}' => $rankPercent,
    '{teacher_name}' => $app['teacher_name'] ?? $teacher
];
$content = strtr($template['content'], $replace);

echo json_encode(['success' => true, 'title' => $template['title'], 'content' => $content], JSON_UNESCAPED_UNICODE);
$conn->close();
