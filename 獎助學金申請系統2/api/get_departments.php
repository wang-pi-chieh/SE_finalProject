<?php
header('Content-Type: application/json');
require 'db_connect.php';

$sql = "SELECT name, category FROM departments ORDER BY category, name";
$result = $conn->query($sql);

$departments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

echo json_encode($departments);
$conn->close();
?>