<?php
// api/get_admin_stats.php
header('Content-Type: application/json');
require 'db_connect.php';

$response = [
    "success" => true,
    "user_count" => 0,
    "application_count" => 0,
    "allocated_budget" => 0,
    "pending_issues" => 0
];

// 1. System Users Count
$sqlUsers = "SELECT COUNT(*) as count FROM users";
$resUsers = $conn->query($sqlUsers);
if ($resUsers && $row = $resUsers->fetch_assoc()) {
    $response['user_count'] = $row['count'];
}

// 2. Total Applications (Current Semester logic can be refined later, currently total)
$sqlApps = "SELECT COUNT(*) as count FROM applications";
$resApps = $conn->query($sqlApps);
if ($resApps && $row = $resApps->fetch_assoc()) {
    $response['application_count'] = $row['count'];
}

// 3. Allocated Budget
// Logic: Sum of (Scholarship Amount) for all APPROVED applications
$sqlBudget = "
    SELECT s.amount
    FROM applications a
    JOIN scholarships s ON a.scholarship_id = s.id
    WHERE a.status = 'approved' OR a.status = '1'
";
$resBudget = $conn->query($sqlBudget);
$total_budget = 0;
if ($resBudget) {
    while ($row = $resBudget->fetch_assoc()) {
        // Remove non-numeric characters (except dot if decimals exist, but usually integers here)
        $amount_str = preg_replace('/[^0-9]/', '', $row['amount']);
        $total_budget += (int) $amount_str;
    }
}
$response['allocated_budget'] = $total_budget;

// 4. Pending Issues
// Count applications that are Pending (3) or Reviewing (4) or Needs Action (2)?
// Assuming 'Pending Issues' means things waiting for admin.
// Screenshot says: 3=Pending.
$sqlPending = "SELECT COUNT(*) as count FROM applications WHERE status = 'pending' OR status = '0' OR status = '3' OR status = '4'";
$resPending = $conn->query($sqlPending);
if ($resPending && $row = $resPending->fetch_assoc()) {
    $response['pending_issues'] = $row['count'];
}

echo json_encode($response);
$conn->close();
?>