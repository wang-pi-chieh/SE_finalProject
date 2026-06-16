<?php
// api/delete_scholarship.php
header('Content-Type: application/json');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(["success" => false, "message" => "Missing ID"]);
    exit;
}

// Optional: Check if there are applications linked?
// For now, simple delete. DB FK might complain if 'applications' refers to it without CASCADE.
// Assuming 'applications' matches 'scholarship_id'.
// To be safe, we might just set is_active=0 (Soft Delete), but user asked for Delete.
// Let's try Delete. If FK constraint exists, we can switch to soft delete or catch error.
// Based on typical user request, 'Delete' means gone.

$stmt = $conn->prepare("DELETE FROM scholarships WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    require_once 'log_utils.php';
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $actor = $_SESSION['username'] ?? 'System Admin';
    logAction($actor, '刪除獎學金', '刪除項目ID: ' . $id);

    echo json_encode(["success" => true]);

} else {
    // Check if error is constraint violation
    if ($conn->errno == 1451) { // Foreign key constraint
        echo json_encode(["success" => false, "message" => "無法刪除：已有學生申請此獎學金。"]);
    } else {
        echo json_encode(["success" => false, "message" => "Delete Failed: " . $conn->error]);
    }
}

$stmt->close();
$conn->close();
?>
