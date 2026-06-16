<?php
require 'api/db_connect.php';

$username = 'admin';

echo "1. Checking User: $username\n";
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    print_r($row);
} else {
    echo "User not found.\n";
}

echo "\n2. Testing get_reviewer_stats.php logic partially:\n";
// mimic the role check logic
$role = '';
if (isset($row)) {
    $role = $row['role'];
    echo "Role: $role\n";
}

// Call API directly via include or curl? simpler to just run it as a script if I modify GET
$_GET['provider_username'] = $username;
ob_start();
include 'api/get_reviewer_stats.php';
$output = ob_get_clean();
echo "\nAPI Output:\n" . $output . "\n";
?>