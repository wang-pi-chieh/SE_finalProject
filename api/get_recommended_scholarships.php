<?php
// api/get_recommended_scholarships.php
header('Content-Type: application/json');
require 'db_connect.php';

// Fetch the "First 3" from database as requested (ID 1, 2, 3)
$sql = "SELECT * FROM scholarships WHERE is_active = 1 ORDER BY id ASC LIMIT 3";
$result = $conn->query($sql);

$scholarships = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $scholarships[] = $row;
    }
}

echo json_encode(["success" => true, "data" => $scholarships]);

$conn->close();
?>