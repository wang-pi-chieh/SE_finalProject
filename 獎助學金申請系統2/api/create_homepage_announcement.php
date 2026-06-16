<?php
// api/create_homepage_announcement.php
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$display_date = $_POST['display_date'] ?? null;
$status_label = $_POST['status_label'] ?? '';
$status_type = $_POST['status_type'] ?? '';

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => '請填寫標題']);
    exit;
}

// 若日期是空字串，就設為 NULL
if ($display_date === '') {
    $display_date = null;
}

try {
    $sql = "INSERT INTO homepage_announcements (title, content, display_date, status_label, status_type, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $title, $content, $display_date, $status_label, $status_type);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>


