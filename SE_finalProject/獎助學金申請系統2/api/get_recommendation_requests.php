<?php
// api/get_recommendation_requests.php
header('Content-Type: application/json');
require 'db_connect.php';
require_once __DIR__ . '/admin/_admin_ops_common.php';
require_once __DIR__ . '/common/mailer.php';

admin_ops_ensure_teacher_notification_schema($conn);

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

sync_teacher_recommendation_notifications($conn, $teacher_username, $teacher_real_name, $requests);

echo json_encode(['success' => true, 'data' => $requests]);

$conn->close();

function sync_teacher_recommendation_notifications($conn, $teacher_username, $teacher_real_name, $requests)
{
    $teacher = get_teacher_contact($conn, $teacher_username, $teacher_real_name);

    foreach ($requests as $request) {
        $notification = build_teacher_recommendation_notification($teacher_username, $request);
        if ($notification === null) {
            continue;
        }

        upsert_teacher_notification_with_email($conn, $teacher, $notification);
    }
}

function build_teacher_recommendation_notification($teacher_username, $request)
{
    $application_id = isset($request['id']) ? (int) $request['id'] : 0;
    if ($application_id <= 0) {
        return null;
    }

    $student_name = isset($request['student_name']) && $request['student_name'] !== ''
        ? (string) $request['student_name']
        : (string) ($request['student_username'] ?? '學生');
    $scholarship_name = (string) ($request['scholarship_name'] ?? '獎助學金');
    $app_status = isset($request['app_status']) ? (int) $request['app_status'] : -1;
    $letter_status = isset($request['letter_status']) ? (string) $request['letter_status'] : '';

    if ($app_status === 2) {
        return [
            'type' => 'needs_action',
            'title' => "{$student_name}同學的申請需補件",
            'message' => "{$student_name}同學申請「{$scholarship_name}」已被退回補件，請關注是否需協助更新推薦內容。",
            'application_id' => $application_id,
            'dedup_key' => "teacher-recommendation:{$teacher_username}:{$application_id}:needs_action"
        ];
    }

    if ($app_status === 1) {
        return [
            'type' => 'approved',
            'title' => "{$student_name}同學的申請已通過",
            'message' => "您為{$student_name}同學撰寫推薦信的「{$scholarship_name}」申請已審核通過。",
            'application_id' => $application_id,
            'dedup_key' => "teacher-recommendation:{$teacher_username}:{$application_id}:approved"
        ];
    }

    if ($letter_status === '1' || $letter_status === 'submitted') {
        return [
            'type' => 'submitted',
            'title' => '您已送出推薦信',
            'message' => "您已送出{$student_name}同學「{$scholarship_name}」申請的推薦信。",
            'application_id' => $application_id,
            'dedup_key' => "teacher-recommendation:{$teacher_username}:{$application_id}:submitted"
        ];
    }

    return null;
}

function upsert_teacher_notification_with_email($conn, $teacher, $notification)
{
    $existing = get_teacher_notification_email_state($conn, $notification['dedup_key']);

    $stmt = $conn->prepare("
        INSERT INTO teacher_notifications
            (teacher_username, type, title, message, related_application_id, dedup_key, is_read)
        VALUES (?, ?, ?, ?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE
            type = VALUES(type),
            title = VALUES(title),
            message = VALUES(message),
            related_application_id = VALUES(related_application_id),
            id = LAST_INSERT_ID(id)
    ");

    if (!$stmt) {
        return;
    }

    $teacher_username = $teacher['username'];
    $type = $notification['type'];
    $title = $notification['title'];
    $message = $notification['message'];
    $application_id = $notification['application_id'];
    $dedup_key = $notification['dedup_key'];

    $stmt->bind_param(
        "ssssis",
        $teacher_username,
        $type,
        $title,
        $message,
        $application_id,
        $dedup_key
    );
    $stmt->execute();
    $notification_id = (int) $conn->insert_id;
    $stmt->close();

    if ($notification_id <= 0 || ($existing && !empty($existing['email_sent_at']))) {
        return;
    }

    $email_error = '';
    $email_sent = send_teacher_notification_gmail($teacher, $notification, $email_error);
    mark_teacher_notification_email_status($conn, $notification_id, $email_sent, $email_error);
}

function get_teacher_notification_email_state($conn, $dedup_key)
{
    $stmt = $conn->prepare("SELECT id, email_sent_at FROM teacher_notifications WHERE dedup_key = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $dedup_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function get_teacher_contact($conn, $teacher_username, $teacher_real_name)
{
    $teacher = [
        'username' => $teacher_username,
        'real_name' => $teacher_real_name,
        'email' => ''
    ];

    $stmt = $conn->prepare("SELECT real_name, email FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) {
        return $teacher;
    }

    $stmt->bind_param("s", $teacher_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $teacher['real_name'] = (string) ($row['real_name'] ?: $teacher_real_name);
        $teacher['email'] = (string) ($row['email'] ?: '');
    }
    $stmt->close();

    return $teacher;
}

function send_teacher_notification_gmail($teacher, $notification, &$error)
{
    if (trim((string) $teacher['email']) === '') {
        $error = '找不到老師 Email';
        return false;
    }

    $safe_title = htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8');
    $safe_message = nl2br(htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'));
    $html = "
        <div style=\"font-family:Arial,'Noto Sans TC',sans-serif;line-height:1.6;color:#111827;\">
            <h2 style=\"margin:0 0 12px;\">NSAMS 老師通知</h2>
            <p style=\"font-weight:bold;margin:0 0 8px;\">{$safe_title}</p>
            <p style=\"margin:0;\">{$safe_message}</p>
        </div>
    ";

    return wu_send_gmail_notification(
        $teacher['email'],
        $teacher['real_name'] ?: $teacher['username'],
        "【NSAMS】{$notification['title']}",
        $html,
        $error
    );
}

function mark_teacher_notification_email_status($conn, $notification_id, $sent, $error)
{
    $error = truncate_teacher_email_error($error);
    $sent_flag = $sent ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE teacher_notifications
        SET email_sent_at = CASE WHEN ? = 1 THEN NOW() ELSE email_sent_at END,
            email_last_error = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("isi", $sent_flag, $error, $notification_id);
    $stmt->execute();
    $stmt->close();
}

function truncate_teacher_email_error($error)
{
    $message = (string) ($error ?: 'Gmail 寄送失敗');
    if (function_exists('mb_substr')) {
        return mb_substr($message, 0, 255, 'UTF-8');
    }
    return substr($message, 0, 255);
}
?>
