<?php
// api/write_recommendation.php
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$teacher_username = $data['teacher_username'] ?? '';
$application_id = $data['application_id'] ?? '';
$content = $data['content'] ?? '';

if (empty($teacher_username) || empty($application_id) || empty($content)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// 寫入資料庫
$sql = "INSERT INTO reference_letters (teacher_username, application_id, content) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sis", $teacher_username, $application_id, $content);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Recommendation submitted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
}

$conn->close();
?>