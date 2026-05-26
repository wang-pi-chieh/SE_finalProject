<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$student = trim($input['student_username'] ?? '');
$scholarshipId = intval($input['scholarship_id'] ?? 0);
$academicYear = trim($input['academic_year'] ?? '');
$semester = trim($input['semester'] ?? '');
$form = $input['form_payload'] ?? [];
$messages = [];
$canSubmit = true;

function fail_with(&$messages, &$canSubmit, $message) {
    $messages[] = $message;
    $canSubmit = false;
}

if ($student === '') fail_with($messages, $canSubmit, '缺少學生帳號。');
if ($scholarshipId <= 0) fail_with($messages, $canSubmit, '缺少獎學金 ID。');

$requiredFields = ['phone' => '電話', 'email' => 'Email', 'academic_year' => '學年', 'semester' => '學期'];
foreach ($requiredFields as $field => $label) {
    $value = trim((string)($form[$field] ?? ($input[$field] ?? '')));
    if ($value === '') fail_with($messages, $canSubmit, $label . '不得空白。');
}

if ($scholarshipId > 0) {
    $schStmt = $conn->prepare("SELECT id, name, application_start_date, application_end_date, description FROM scholarships WHERE id = ? AND is_active = 1");
    $schStmt->bind_param('i', $scholarshipId);
    $schStmt->execute();
    $scholarship = $schStmt->get_result()->fetch_assoc();
    $schStmt->close();
    if (!$scholarship) {
        fail_with($messages, $canSubmit, '找不到可申請的獎學金。');
    } else {
        $today = date('Y-m-d');
        if (!empty($scholarship['application_start_date']) && $today < $scholarship['application_start_date']) {
            fail_with($messages, $canSubmit, '尚未開放申請。');
        }
        if (!empty($scholarship['application_end_date']) && $today > $scholarship['application_end_date']) {
            fail_with($messages, $canSubmit, '已超過申請期限。');
        }
    }
}

if ($student !== '' && $scholarshipId > 0 && $academicYear !== '' && $semester !== '') {
    $dup = $conn->prepare("SELECT id FROM applications WHERE student_username = ? AND scholarship_id = ? AND academic_year = ? AND semester = ? LIMIT 1");
    $dup->bind_param('siss', $student, $scholarshipId, $academicYear, $semester);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        fail_with($messages, $canSubmit, '本學年學期已申請過此獎學金，不可重複送出。');
    }
    $dup->close();
}

if ($student !== '') {
    $gradeStmt = $conn->prepare("SELECT avg_score, class_rank, class_size FROM grades WHERE student_username = ? ORDER BY academic_year DESC, semester DESC LIMIT 1");
    $gradeStmt->bind_param('s', $student);
    $gradeStmt->execute();
    $grade = $gradeStmt->get_result()->fetch_assoc();
    $gradeStmt->close();
    if ($grade && intval($grade['class_size']) > 0) {
        $rankPercent = intval($grade['class_rank']) / intval($grade['class_size']);
        $messages[] = '成績資料已檢查：班排百分比約 ' . round($rankPercent * 100, 1) . '%。';
    } else {
        $messages[] = '沒有最近成績資料，若獎學金需要成績門檻，請補齊證明文件。';
    }
}

if ($canSubmit && empty($messages)) $messages[] = '檢查通過，可以送出申請。';

echo json_encode(['success' => true, 'can_submit' => $canSubmit, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
$conn->close();
