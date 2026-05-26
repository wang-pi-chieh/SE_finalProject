<?php
// api/get_recommendation_requests.php
header('Content-Type: application/json');
require 'db_connect.php';

// Check if user is logged in as teacher
// ideally this should check session or token, but for now we follow existing pattern or rely on query param for MVP
// Existing pattern uses `users` table check? No, usually frontend stores user info.
// We will accept `teacher_username` as a GET parameter for flexibility, but in a real app this should be secured.

$teacher_username = $_GET['teacher_username'] ?? '';

if (empty($teacher_username)) {
    echo json_encode(['success' => false, 'message' => 'Missing teacher_username']);
    exit;
}

// Check status logic:
// existing UI has: Pending, Draft, Submitted
// DB status enum: 'pending', 'reviewing', 'approved', 'rejected', 'needs_action'
// Let's map:
// Pending (待撰寫) -> status = 'pending' AND referrer_username = teacher
// Draft (草稿) -> internally managed by teacher? or is it 'reviewing'?
// Submitted (已提交) -> 'approved' or special status?
//
// For this task, we will just fetch applications assigned to this teacher.

$sql = "
    SELECT 
        a.id,
        a.student_username,
        u.real_name as student_name,
        s.department as student_dept,
        sc.name as scholarship_name,
        a.scholarship_id,
        a.application_date,
        a.status
    FROM applications a
    JOIN users u ON a.student_username = u.username
    LEFT JOIN students s ON a.student_username = s.username
    JOIN scholarships sc ON a.scholarship_id = sc.id
    WHERE a.referrer_username = ? OR (a.referrer_name = ? AND a.referrer_name IS NOT NULL AND a.referrer_name != '')
    ORDER BY a.application_date DESC
";

// Get teacher's real name to support fallback by name
$name_sql = "SELECT real_name FROM users WHERE username = ?";
$name_stmt = $conn->prepare($name_sql);
$name_stmt->bind_param("s", $teacher_username);
$name_stmt->execute();
$name_res = $name_stmt->get_result();
$teacher_real_name = '';
if ($row = $name_res->fetch_assoc()) {
    $teacher_real_name = $row['real_name'];
}
$name_stmt->close();

$sql = "
    SELECT 
        a.id,
        a.student_username,
        u.real_name as student_name,
        s.department as student_dept,
        sc.name as scholarship_name,
        a.scholarship_id,
        a.application_date,
        a.status as app_status,
        rl.id as letter_id,
        rl.status as letter_status,
        rl.content as letter_content,
        rl.file_path as letter_file_path,
        (SELECT ROUND(AVG(g.gpa), 1) FROM grades g WHERE g.student_username = a.student_username) as student_gpa
    FROM applications a
    JOIN users u ON a.student_username = u.username
    LEFT JOIN students s ON a.student_username = s.username
    JOIN scholarships sc ON a.scholarship_id = sc.id
    LEFT JOIN reference_letters rl ON a.id = rl.application_id
    WHERE a.referrer_username = ? OR (a.referrer_name = ? AND a.referrer_name IS NOT NULL AND a.referrer_name != '')
    ORDER BY a.application_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $teacher_username, $teacher_real_name);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode(['success' => true, 'data' => $requests]);

$conn->close();
?>