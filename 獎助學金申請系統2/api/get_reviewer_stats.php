<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check auth (simplified for now)
$provider_username = isset($_GET['provider_username']) ? $_GET['provider_username'] : '';
if (empty($provider_username)) {
    echo json_encode(['success' => false, 'message' => 'Missing provider_username']);
    exit;
}

$stats = [
    'pending_count' => 0, // Code 3 (or 0)
    'urgent_count' => 0, // Code 2
    'rejected_count' => 0, // Code 0 (or 2 legacy)
    'approved_count' => 0, // Code 1
    'needs_action_count' => 0, // Code 2
    'reviewing_count' => 0, // Code 4
    'total_applications' => 0,
    'completed_count' => 0,
    'reviewed_today_count' => 0,
    'total_amount' => 0,
    'distribution' => []
];

// 0. Get User Role
$role = '';
$stmt_role = $conn->prepare("SELECT role FROM users WHERE username = ?");
$stmt_role->bind_param("s", $provider_username);
$stmt_role->execute();
$res_role = $stmt_role->get_result();
if ($row = $res_role->fetch_assoc()) {
    $role = $row['role'];
} else {
    // If not found, maybe admin? or error. Treat as none or error.
    // For safety, if user not found, return empty stats.
    echo json_encode(['success' => true, 'data' => $stats]);
    exit;
}

// Prepare filter clause
$filter_sql = "";
$filter_types = "";
$filter_params = [];

if ($role === 'scholarship_unit' || $role === '獎助單位') {
    // Filter by scholarship provider + Visibility Logic
    $filter_sql = " JOIN scholarships s_filter ON application_table.scholarship_id = s_filter.id 
                    LEFT JOIN reference_letters rl_filter ON application_table.id = rl_filter.application_id 
                    WHERE s_filter.provider_username = ? 
                    AND (application_table.recommendation_required = 0 OR (application_table.recommendation_required = 1 AND rl_filter.status = 1)) ";
    $filter_types = "s";
    $filter_params[] = $provider_username;
} else {
    // Admin sees all + Visibility Logic (Admin should see all? User said "Reviewer dashboard". Usually admin sees everything even pending recommendation?)
    // But user request context implies "Reviewer" (Unit).
    // Let's assume Admin generally wants to see everything, OR apply same logic if "Reviewer Dashboard" is shared.
    // The user said "Reviewer View". Usually Admin is superuser. 
    // BUT the dashboard logic sharing suggests consistent behavior for "Verification".
    // Let's apply visibility logic to Admin too if they are using this endpoint for overview, 
    // OR keep strict. The prompt implies "Reviewer" (Unit) specifically for the visibility.
    // However, consistency is safer to match the list view which we updated for both? 
    // Actually list view `get_reviewer_applications.php` updates applied to query generally, so yes.

    $filter_sql = " LEFT JOIN reference_letters rl_filter ON application_table.id = rl_filter.application_id 
                    WHERE 1=1 
                    AND (application_table.recommendation_required = 0 OR (application_table.recommendation_required = 1 AND rl_filter.status = 1)) ";
}

// Helper to execute query with filter
function executeQuery($conn, $baseSql, $filter_sql, $filter_types, $filter_params)
{
    // Replace 'application_table' alias if needed or just append
    // Note: My $filter_sql uses 'application_table' as table alias placeholder or assumes table name?
    // Let's make it consistent. We will alias applications table as 'a' in queries.

    // Adjust filter sql for usage
    $final_filter = str_replace("application_table", "a", $filter_sql);

    $full_sql = $baseSql . $final_filter;
    // Special handling for GROUP BY which comes after WHERE
    if (strpos($baseSql, "GROUP BY") !== false) {
        $parts = explode("GROUP BY", $baseSql);
        $full_sql = $parts[0] . $final_filter . " GROUP BY " . $parts[1];
    }

    $stmt = $conn->prepare($full_sql);
    if (!empty($filter_types)) {
        $stmt->bind_param($filter_types, ...$filter_params);
    }
    $stmt->execute();
    return $stmt->get_result();
}


// 1. Status Counts
// Base: FROM applications a ...
$sqlStatus = "SELECT a.status, COUNT(*) as count FROM applications a ";
// Need to join? Filter SQL already acts as join if needed or where.
// But my $filter_sql has JOIN in it if scholarship_unit.
// If admin, it is "WHERE 1=1".
// So:
$sqlStatusFull = "SELECT a.status, COUNT(*) as count FROM applications a ";
$statusFilterSql = str_replace("application_table", "a", $filter_sql); // Use 'a' as alias
$sqlStatusFull .= $statusFilterSql . " GROUP BY a.status";

$stmt = $conn->prepare($sqlStatusFull);
if (!empty($filter_types)) {
    $stmt->bind_param($filter_types, ...$filter_params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $s = (int) $row['status']; // Cast to int
    $c = (int) $row['count'];

    // Map to stats (0:Rejected, 1:Approved, 2:Revision, 3:Pending)
    if ($s === 1) {
        $stats['approved_count'] += $c;
    } elseif ($s === 0) {
        $stats['rejected_count'] += $c;
    } elseif ($s === 2) {
        $stats['needs_action_count'] += $c;
        $stats['urgent_count'] += $c;
    } elseif ($s === 3) {
        $stats['pending_count'] += $c;
    } else {
        // Unknown, treat as pending
        $stats['pending_count'] += $c;
    }
}

$stats['total_applications'] = $stats['rejected_count'] + $stats['approved_count'] + $stats['needs_action_count'] + $stats['pending_count'];
$stats['completed_count'] = $stats['rejected_count'] + $stats['approved_count'] + $stats['needs_action_count']; // Pending is not completed

// 2. Reviewed Today
$sqlTodayBase = "SELECT COUNT(*) as count FROM applications a ";
$sqlTodayFull = $sqlTodayBase . $statusFilterSql . " AND DATE(a.reviewed_at) = CURDATE()";
// Fix: if filter already has WHERE, append with AND.
// My filter_sql is " JOIN ... WHERE ..." or " WHERE 1=1 ".
// So it is safe to append " AND ..."
$stmt = $conn->prepare($sqlTodayFull);
if (!empty($filter_types)) {
    $stmt->bind_param($filter_types, ...$filter_params);
}
$stmt->execute();
$resToday = $stmt->get_result();
if ($row = $resToday->fetch_assoc()) {
    $stats['reviewed_today_count'] = (int) $row['count'];
}

// 3. Total Amount (Status = 1)
// Apply visibility filter even if Status=1 (Approved), though Approved usually implies recommendation done.
// But for consistency let's stick to the logic. Actually if Status=1, rec must be done/not needed effectively.
// But technically `recommendation_required` could be 1 and `rl.status` could be null if manually updated DB?
// Safe to just add the filter.
$visibility_join = " LEFT JOIN reference_letters rl ON a.id = rl.application_id ";
$visibility_cond = " AND (a.recommendation_required = 0 OR (a.recommendation_required = 1 AND rl.status = 1)) ";

$sqlAmountBase = "SELECT SUM(s.amount) as total FROM applications a JOIN scholarships s ON a.scholarship_id = s.id " . $visibility_join;

if ($role === 'scholarship_unit' || $role === '獎助單位') {
    $sqlAmount = $sqlAmountBase . " WHERE a.status = 1 AND s.provider_username = ? " . $visibility_cond;
    $stmt = $conn->prepare($sqlAmount);
    $stmt->bind_param("s", $provider_username);
} else {
    $sqlAmount = $sqlAmountBase . " WHERE a.status = 1 " . $visibility_cond;
    $stmt = $conn->prepare($sqlAmount);
}
$stmt->execute();
$resAmount = $stmt->get_result();
if ($row = $resAmount->fetch_assoc()) {
    $stats['total_amount'] = (int) $row['total'];
}

// 4. Distribution
$sqlDistBase = "SELECT s.name, COUNT(a.id) as count FROM applications a JOIN scholarships s ON a.scholarship_id = s.id " . $visibility_join;
if ($role === 'scholarship_unit' || $role === '獎助單位') {
    $sqlDist = $sqlDistBase . " WHERE s.provider_username = ? " . $visibility_cond . " GROUP BY s.name ORDER BY count DESC";
    $stmt = $conn->prepare($sqlDist);
    $stmt->bind_param("s", $provider_username);
} else {
    $sqlDist = $sqlDistBase . " WHERE 1=1 " . $visibility_cond . " GROUP BY s.name ORDER BY count DESC";
    $stmt = $conn->prepare($sqlDist);
}
$stmt->execute();
$resDist = $stmt->get_result();
while ($row = $resDist->fetch_assoc()) {
    $stats['distribution'][] = [
        'name' => $row['name'],
        'count' => (int) $row['count']
    ];
}

echo json_encode(['success' => true, 'data' => $stats]);
$conn->close();
?>