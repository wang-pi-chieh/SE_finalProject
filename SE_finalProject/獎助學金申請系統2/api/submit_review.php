<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
session_start();

// 1. Check Authentication (Reviewer/Admin only)
// For now, we assume if you are logged in, you can review, or we should check role.
// Adjust role check as needed. 
/*
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'scholarship_unit') {
   // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
   // exit;
}
*/
// For demo/simplicity, using a default admin username if not set, or trusting session.
$reviewer_username = $_SESSION['username'] ?? 'admin'; // Fallback for debugging

// 2. Get Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$application_id = $input['application_id'] ?? null;
$score = 0; // Score removed from UI
$status_text = $input['status'] ?? null; // 'approved' or 'rejected'
$comment = $input['comment'] ?? '';

if (!$application_id || !isset($status_text)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Map numeric status codes (0, 1, 2, 3)
// 0: Rejected, 1: Approved, 2: Revision, 3: Pending
$db_status = 3; // Default

if ($status_text === '1' || $status_text === 1 || $status_text === 'approved' || $status_text === '通過') {
    $db_status = 1;
} elseif ($status_text === '2' || $status_text === 2 || $status_text === 'supplement' || $status_text === 'needs_action' || $status_text === '需補件') {
    $db_status = 2;
} elseif ($status_text === '0' || $status_text === 0 || $status_text === 'rejected' || $status_text === '駁回') {
    $db_status = 0;
} elseif ($status_text === '3' || $status_text === 3 || $status_text === 'reviewing' || $status_text === '未審查' || $status_text === 'pending') {
    $db_status = 3;
} else {
    // If it's number, assume it matches
    if (is_numeric($status_text)) {
        $db_status = (int) $status_text;
    } else {
        $db_status = 3;
    }
}
$numeric_result = (string) $db_status;

$conn->begin_transaction();

try {
    // 3. Update Application Status (Removed Score)
    // Note: status column is likely VARCHAR, so we store '1', '2', '3' string representations or rely on loose typing
    $stmt = $conn->prepare("UPDATE applications SET status = ?, review_comment = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("issi", $db_status, $comment, $reviewer_username, $application_id);

    if (!$stmt->execute()) {
        throw new Exception("Update application failed: " . $stmt->error);
    }

    // 4. Insert Review Record (History)
    // Note: 'review_records' uses 'result' column which is varchar, 'note' for comment
    // Score column removed as it does not exist in the table
    $stmt_hist = $conn->prepare("INSERT INTO review_records (application_id, review_date, result, note, admin_username) VALUES (?, CURRENT_DATE, ?, ?, ?)");
    $stmt_hist->bind_param("isss", $application_id, $numeric_result, $comment, $reviewer_username);

    if (!$stmt_hist->execute()) {
        throw new Exception("Insert review record failed: " . $stmt_hist->error);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>