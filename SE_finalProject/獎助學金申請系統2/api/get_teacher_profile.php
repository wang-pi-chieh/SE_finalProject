<?php
// api/get_teacher_profile.php
header('Content-Type: application/json');
require 'db_connect.php';

$username = $_GET['username'] ?? '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Missing username"]);
    exit;
}

$sql = "SELECT u.username, u.real_name, u.role, u.email, u.phone, u.avatar_url, 
               t.department, t.position 
        FROM users u 
        LEFT JOIN teachers t ON u.username = t.username 
        WHERE u.username = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Determine title (position)
    $position = $row['position'];
    if (empty($position)) {
        $position = '教授'; // Default to Professor if not set
    }
    $row['display_position'] = $position;

    echo json_encode(["success" => true, "data" => $row]);
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}
