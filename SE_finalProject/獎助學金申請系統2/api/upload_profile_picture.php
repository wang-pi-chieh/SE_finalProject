<?php
// api/upload_profile_picture.php
header('Content-Type: application/json');
require 'db_connect.php';
require_once __DIR__ . '/common/upload_storage.php';

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

// Check if file is uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "No file uploaded or upload error"]);
    exit;
}

$username = $_POST['username'] ?? '';
if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Missing username"]);
    exit;
}

$file = $_FILES['avatar'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validation
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(["success" => false, "message" => "Invalid file type. Only JPG, PNG, GIF, WebP allowed."]);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(["success" => false, "message" => "File too large. Max 5MB."]);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $username . '_' . time() . '.' . $ext;

// Move file
try {
    $dbPath = upload_storage_move_uploaded_file($file['tmp_name'], 'avatars', $filename);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($dbPath !== null) {

    // Update DB
    $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE username = ?");
    $stmt->bind_param("ss", $dbPath, $username);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Upload successful",
            "path" => $dbPath,
            "url" => upload_storage_download_url($dbPath)
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["success" => false, "message" => "Database update failed: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Failed to move uploaded file"]);
}

$conn->close();
?>
