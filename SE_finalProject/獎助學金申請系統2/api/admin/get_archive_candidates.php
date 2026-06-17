<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_teacher_notification_schema($conn);

$archive_type = isset($_GET['archive_type']) ? $_GET['archive_type'] : '';

if ($archive_type === 'departed_teachers') {
    $stmt = $conn->prepare("
        SELECT u.username, u.real_name, u.email, u.phone,
               t.department, t.position,
               COUNT(DISTINCT rl.id) AS reference_letter_count,
               COUNT(DISTINCT a.id) AS referred_application_count,
               COUNT(DISTINCT tn.id) AS notification_count
        FROM users u
        INNER JOIN teachers t ON t.username = u.username
        LEFT JOIN reference_letters rl ON rl.teacher_username = u.username
        LEFT JOIN applications a ON a.referrer_username = u.username
        LEFT JOIN teacher_notifications tn ON tn.teacher_username = u.username
        WHERE u.role IN ('老師', 'teacher')
        GROUP BY u.username, u.real_name, u.email, u.phone, t.department, t.position
        ORDER BY u.username ASC
    ");

    if (!$stmt || !$stmt->execute()) {
        admin_ops_json(['success' => false, 'message' => '讀取老師候選資料失敗。'], 500);
    }

    $result = $stmt->get_result();
    $candidates = [];
    while ($row = $result->fetch_assoc()) {
        $row['reference_letter_count'] = (int) $row['reference_letter_count'];
        $row['referred_application_count'] = (int) $row['referred_application_count'];
        $row['notification_count'] = (int) $row['notification_count'];
        $candidates[] = $row;
    }

    $stmt->close();
    $conn->close();

    admin_ops_json([
        'success' => true,
        'data' => $candidates
    ]);
}

if ($archive_type !== 'students_over_years') {
    admin_ops_json(['success' => true, 'data' => []]);
}

$cutoff_year = isset($_GET['cutoff_year']) ? (int) $_GET['cutoff_year'] : (int) date('Y') - 1911 - 4;

$stmt = $conn->prepare("
    SELECT u.username, u.real_name, u.email, u.phone,
           s.department, s.grade_level, s.class_name
    FROM users u
    INNER JOIN students s ON s.username = u.username
    WHERE u.role = '學生'
    ORDER BY u.username ASC
");

if (!$stmt || !$stmt->execute()) {
    admin_ops_json(['success' => false, 'message' => '讀取學生候選資料失敗。'], 500);
}

$result = $stmt->get_result();
$candidates = [];
while ($row = $result->fetch_assoc()) {
    $admission_year = null;
    if (preg_match('/(\d{3})/', (string) $row['username'], $matches)) {
        $admission_year = (int) $matches[1];
    }
    if ($admission_year !== null && $admission_year <= $cutoff_year) {
        $row['admission_year'] = $admission_year;
        $candidates[] = $row;
    }
}

$stmt->close();
$conn->close();

admin_ops_json([
    'success' => true,
    'data' => $candidates,
    'cutoff_year' => $cutoff_year
]);
?>
