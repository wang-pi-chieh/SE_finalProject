<?php
// api/update_homepage_announcement.php
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$display_date = $_POST['display_date'] ?? null;
$status_label = $_POST['status_label'] ?? '';
$status_type = $_POST['status_type'] ?? '';

if (empty($id) || empty($title)) {
    echo json_encode(['success' => false, 'message' => '缺少必要欄位（ID 或 標題）']);
    exit;
}

if ($display_date === '') {
    $display_date = null;
}

try {
    $sql = "UPDATE homepage_announcements 
            SET title = ?, content = ?, display_date = ?, status_label = ?, status_type = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $title, $content, $display_date, $status_label, $status_type, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
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


