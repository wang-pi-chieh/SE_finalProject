<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

try {
    // Select users who are scholarship units
    // Join with scholarship_units table to get unit_name if available, or just use real_name from users?
    // The scholarship_units table has unit_name.

    $sql = "SELECT u.username, su.unit_name 
            FROM users u 
            JOIN scholarship_units su ON u.username = su.username 
            WHERE u.role = 'scholarship_unit' OR u.role = '獎助單位'";

    $result = $conn->query($sql);

    $units = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $units]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>