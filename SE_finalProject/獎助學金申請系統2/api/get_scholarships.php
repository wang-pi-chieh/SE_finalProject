<?php
// api/get_scholarships.php
header('Content-Type: application/json');
require 'db_connect.php';

// 只顯示啟用且未過期的獎學金 (可選邏輯)
// 若要顯示所有，可移除 WHERE 條件
$sql = "SELECT s.*, su.unit_name 
        FROM scholarships s
        LEFT JOIN scholarship_units su ON s.provider_username = su.username
        WHERE s.is_active = 1 
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);

$scholarships = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $scholarships[] = $row;
    }
}

echo json_encode(["success" => true, "data" => $scholarships]);

$conn->close();
?>