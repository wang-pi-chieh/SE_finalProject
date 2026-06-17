<?php
// api/search_teachers.php
header('Content-Type: application/json');
require 'db_connect.php';

$query = $_GET['query'] ?? '';

if (empty($query)) {
    echo json_encode(["success" => true, "data" => []]);
    exit;
}

$searchTerm = "%" . $query . "%";
$sql = "SELECT users.username, users.real_name, teachers.department 
        FROM users 
        JOIN teachers ON users.username = teachers.username 
        WHERE users.role = 'teacher' AND (users.real_name LIKE ? OR teachers.department LIKE ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

echo json_encode(["success" => true, "data" => $teachers]);

$stmt->close();
$conn->close();
?>