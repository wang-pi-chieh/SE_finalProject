<?php
// api/debug_stats.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';

$provider_username = $_GET['u'] ?? 'admin';
echo "Testing with username: $provider_username<br>";

// Copy-paste logic from get_reviewer_stats.php (simplified to test breakdown)

$stats = [];

// 0. Get User Role
$role = '';
$stmt_role = $conn->prepare("SELECT role FROM users WHERE username = ?");
$stmt_role->bind_param("s", $provider_username);
$stmt_role->execute();
$res_role = $stmt_role->get_result();
if ($row = $res_role->fetch_assoc()) {
    $role = $row['role'];
    echo "Found Role: $role<br>";
} else {
    echo "User not found<br>";
}

$filter_sql = "";
$filter_types = "";
$filter_params = [];

if ($role === 'scholarship_unit' || $role === '獎助單位') {
    $filter_sql = " JOIN scholarships s_filter ON application_table.scholarship_id = s_filter.id WHERE s_filter.provider_username = ? ";
    $filter_types = "s";
    $filter_params[] = $provider_username;
} else {
    $filter_sql = " WHERE 1=1 ";
}
echo "Filter SQL (raw): $filter_sql<br>";


// 1. Status Counts
echo "<h3>1. Status Counts</h3>";
$sqlStatusFull = "SELECT a.status, COUNT(*) as count FROM applications a ";
$statusFilterSql = str_replace("application_table", "a", $filter_sql);
$sqlStatusFull .= $statusFilterSql . " GROUP BY a.status";
echo "SQL: $sqlStatusFull<br>";

echo "<h3>Debug Tables</h3>";

// Check Users
$c = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
echo "Users count: $c <br>";

// Check Applications
$c = $conn->query("SELECT COUNT(*) as c FROM applications")->fetch_assoc()['c'];
echo "Applications count: $c <br>";

// Check distinct statuses
$res = $conn->query("SELECT DISTINCT status FROM applications");
echo "Distinct Statuses: ";
while ($row = $res->fetch_assoc()) {
    echo "[" . $row['status'] . "] ";
}
echo "<br>";

// Run the targeted query again
echo "<h3>Target Query Again</h3>";
$stmt = $conn->prepare($sqlStatusFull);
if (!empty($filter_types)) {
    $stmt->bind_param($filter_types, ...$filter_params);
}
if ($stmt->execute()) {
    $result = $stmt->get_result();
    echo "Num rows: " . $result->num_rows . "<br>";
    while ($row = $result->fetch_assoc()) {
        echo "Status: " . var_export($row['status'], true) . " Count: " . $row['count'] . "<br>";
    }
} else {
    echo "Execute failed: " . $stmt->error;
}


echo "<h3>Done</h3>";
?>