<?php
// api/migrate_dept_admin.php
header('Content-Type: application/json');
require 'db_connect.php';

$response = [];

// 1. Update users table: '系管' -> '系統管理員'
$updateSql = "UPDATE users SET role = '系統管理員' WHERE role = '系管'";
if ($conn->query($updateSql) === TRUE) {
    $response['users_updated'] = $conn->affected_rows;
} else {
    $response['users_error'] = $conn->error;
}

// 2. Ensure all '系統管理員' (including those just updated) are in system_admins table
$selectSql = "SELECT username FROM users WHERE role = '系統管理員' OR role = 'system_admin'";
$result = $conn->query($selectSql);

$inserted = 0;
if ($result->num_rows > 0) {
    $stmt = $conn->prepare("INSERT IGNORE INTO system_admins (username) VALUES (?)");
    while ($row = $result->fetch_assoc()) {
        $stmt->bind_param("s", $row['username']);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $inserted++;
        }
    }
    $stmt->close();
}
$response['system_admins_inserted'] = $inserted;

echo json_encode($response);
$conn->close();
?>