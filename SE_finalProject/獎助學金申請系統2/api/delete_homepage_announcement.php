<?php
// api/delete_homepage_announcement.php
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

// Accept either JSON body or form-data
$raw = file_get_contents('php://input');
$id = null;
if (!empty($_POST['id'])) {
    $id = (int)$_POST['id'];
} elseif (!empty($raw)) {
    $data = json_decode($raw, true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
    }
}

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Missing id']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM homepage_announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
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


