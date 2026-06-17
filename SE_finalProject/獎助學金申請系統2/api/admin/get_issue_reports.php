<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';

admin_ops_ensure_issue_schema($conn);

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowed_statuses = ['open', 'processing', 'resolved'];

$select_sql = "
    SELECT id, reporter_username, reporter_role, title, description,
           contact_email, contact_phone, status, created_at, updated_at
    FROM issue_reports
";

if ($status !== '' && in_array($status, $allowed_statuses, true)) {
    $stmt = $conn->prepare($select_sql . "
        WHERE status = ?
        ORDER BY FIELD(status, 'open', 'processing', 'resolved'), created_at DESC
        LIMIT 100
    ");
    $stmt->bind_param("s", $status);
} else {
    $stmt = $conn->prepare($select_sql . "
        ORDER BY FIELD(status, 'open', 'processing', 'resolved'), created_at DESC
        LIMIT 100
    ");
}

if (!$stmt || !$stmt->execute()) {
    admin_ops_json(['success' => false, 'message' => '讀取問題回報失敗'], 500);
}

$result = $stmt->get_result();
$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

$stmt->close();
$conn->close();

admin_ops_json(['success' => true, 'data' => $reports]);
?>
