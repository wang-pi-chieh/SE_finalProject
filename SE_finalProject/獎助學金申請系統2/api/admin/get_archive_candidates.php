<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';

$archive_type = isset($_GET['archive_type']) ? $_GET['archive_type'] : '';

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
