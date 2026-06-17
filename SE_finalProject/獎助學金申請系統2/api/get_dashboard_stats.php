<?php
// api/get_dashboard_stats.php
header('Content-Type: application/json');
require 'db_connect.php';

$username = $_GET['student_username'] ?? '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Missing student_username"]);
    exit;
}

// Initialize default stats
$stats = [
    'reviewing' => 0,
    'passed' => 0,
    'needs_action' => 0,
    'total_amount' => 0
];

// Query for counts
$sql = "SELECT status, COUNT(*) as count FROM applications WHERE student_username = ? GROUP BY status";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $s = $row['status']; // Now an integer (or string representation of int)
    $c = (int) $row['count'];

    // 0=Rejected, 1=Approved, 2=NeedsAction, 3=Pending, 4=Reviewing
    if ($s == 3 || $s == 4 || $s == '3' || $s == '4') {
        $stats['reviewing'] += $c;
    } elseif ($s == 1 || $s == '1') {
        $stats['passed'] += $c;
    } elseif ($s == 2 || $s == '2') {
        $stats['needs_action'] += $c;
    }
    // Rejected (0) skipped
}

$stmt->close();

// Query for total amount (only approved = 1)
$sqlAmount = "SELECT SUM(s.amount) as total 
              FROM applications a 
              JOIN scholarships s ON a.scholarship_id = s.id 
              WHERE a.student_username = ? AND a.status = 1";
$stmtAmount = $conn->prepare($sqlAmount);
$stmtAmount->bind_param("s", $username);
$stmtAmount->execute();
$resAmount = $stmtAmount->get_result();
if ($row = $resAmount->fetch_assoc()) {
    $stats['total_amount'] = (int) $row['total'];
}
$stmtAmount->close();

echo json_encode(["success" => true, "data" => $stats]);

$conn->close();
?>