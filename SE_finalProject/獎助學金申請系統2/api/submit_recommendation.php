<?php
// api/submit_recommendation.php
header('Content-Type: application/json');
require 'db_connect.php';

// Handle POST request with FormData (Files)
$teacher_username = $_POST['teacher_username'] ?? '';
$application_id = $_POST['application_id'] ?? '';
$content = $_POST['content'] ?? '';
$type = $_POST['type'] ?? 'submit';

if (empty($teacher_username) || empty($application_id)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Handle File Upload
$file_path = null;
if (isset($_FILES['file-upload']) && $_FILES['file-upload']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/recommendations/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmpPath = $_FILES['file-upload']['tmp_name'];
    $fileName = $_FILES['file-upload']['name'];
    $fileSize = $_FILES['file-upload']['size'];
    $fileType = $_FILES['file-upload']['type'];

    // Generate unique name: rec_{app_id}_{timestamp}_{original}
    $newFileName = 'rec_' . $application_id . '_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "", $fileName);
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        // Store relative path in DB
        $file_path = 'uploads/recommendations/' . $newFileName;
    } else {
        echo json_encode(["success" => false, "message" => "File upload failed"]);
        exit;
    }
}

// Check exists
$check_sql = "SELECT id FROM reference_letters WHERE application_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$res = $stmt->get_result();
$existing = $res->fetch_assoc();
$stmt->close();

if ($existing) {
    // Update
    // If file uploaded, update file_path, else keep old? 
    // Usually if user doesn't upload new file, keep old.
    // But if file_path is null in script, we need to know if we should overwrite with NULL.
    // If file_path is NOT null,
    // Determine status: 1 = Submitted, 2 = Draft
    $status = ($type === 'draft') ? '2' : '1';

    // Update existing
    $sql = "UPDATE reference_letters SET teacher_username = ?, content = ?, filled_at = NOW(), status = ?";
    $types = "sss";
    $params = [$teacher_username, $content, $status];

    if ($file_path) {
        $sql .= ", file_path = ?";
        $types .= "s";
        $params[] = $file_path;
    }

    $sql .= " WHERE application_id = ?";
    $types .= "i";
    $params[] = $application_id;

    if (!$stmt = $conn->prepare($sql)) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        exit;
    }

    // bind_param requires references
    $bind_params = [];
    $bind_params[] = &$types;
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_params);

} else {
    // Insert
    // Status: 1 = Submitted, 2 = Draft (Pending Logic 0 is for no record)
    $status = ($type === 'draft') ? 2 : 1;
    $sql = "INSERT INTO reference_letters (teacher_username, application_id, content, filled_at, file_path, status) VALUES (?, ?, ?, NOW(), ?, ?)";
    if (!$stmt = $conn->prepare($sql)) {
        echo json_encode(["success" => false, "message" => "Prepare failed (Insert): " . $conn->error]);
        exit;
    }
    $stmt->bind_param("sisss", $teacher_username, $application_id, $content, $file_path, $status); // 's' for teacher_username, 'i' for application_id, 's' for content, 's' for file_path, 's' for status
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Recommendation saved successfully"]);

    // If submitted (not draft), we don't need to manually update application status to 'pending'
    // because it's already 'pending' (3). The reviewer visibility query handles the logic.
    // So we do nothing here regarding application status.

} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>