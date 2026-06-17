<?php
// api/update_scholarship.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

function ensureReviewCompletedColumn($conn)
{
    $sql = "SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'scholarships'
              AND COLUMN_NAME = 'review_completed'";
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;

    if ((int) ($row['total'] ?? 0) > 0) {
        return true;
    }

    return $conn->query("ALTER TABLE scholarships ADD COLUMN review_completed TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否審核完成' AFTER is_active") === true;
}

if (!ensureReviewCompletedColumn($conn)) {
    echo json_encode(["success" => false, "message" => "無法建立審核完成欄位：" . $conn->error]);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$amount = trim($_POST['amount'] ?? '');
$quota = isset($_POST['quota']) ? (int) $_POST['quota'] : 0;
$description = trim($_POST['description'] ?? '');
$applicationStartDate = trim($_POST['application_start_date'] ?? '');
$applicationEndDate = trim($_POST['application_end_date'] ?? '');
$providerUsername = trim($_POST['provider_username'] ?? '');
$isActive = ($_POST['is_active'] ?? '0') === '1' ? 1 : 0;
$reviewCompleted = ($_POST['review_completed'] ?? '0') === '1' ? 1 : 0;

if ($id <= 0 || $name === '' || $amount === '' || $applicationEndDate === '' || $providerUsername === '') {
    echo json_encode(["success" => false, "message" => "請填寫必要欄位"]);
    exit;
}

$checkScholarship = $conn->prepare("SELECT id FROM scholarships WHERE id = ?");
$checkScholarship->bind_param("i", $id);
$checkScholarship->execute();
$scholarshipExists = $checkScholarship->get_result()->num_rows > 0;
$checkScholarship->close();

if (!$scholarshipExists) {
    echo json_encode(["success" => false, "message" => "找不到指定的獎學金項目"]);
    exit;
}

$checkUnit = $conn->prepare("SELECT username FROM scholarship_units WHERE username = ?");
$checkUnit->bind_param("s", $providerUsername);
$checkUnit->execute();
$unitExists = $checkUnit->get_result()->num_rows > 0;
$checkUnit->close();

if (!$unitExists) {
    echo json_encode(["success" => false, "message" => "指定的獎助單位不存在"]);
    exit;
}

$applicationStartDate = $applicationStartDate !== '' ? $applicationStartDate : null;

$stmt = $conn->prepare("
    UPDATE scholarships
    SET name = ?,
        description = ?,
        amount = ?,
        quota = ?,
        application_start_date = ?,
        application_end_date = ?,
        provider_username = ?,
        is_active = ?,
        review_completed = ?
    WHERE id = ?
");

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "DB Error: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "sssisssiii",
    $name,
    $description,
    $amount,
    $quota,
    $applicationStartDate,
    $applicationEndDate,
    $providerUsername,
    $isActive,
    $reviewCompleted,
    $id
);

if ($stmt->execute()) {
    require_once 'log_utils.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $actor = $_SESSION['username'] ?? 'System Admin';
    logAction($actor, '更新獎學金', '更新獎學金ID: ' . $id . '，名稱: ' . $name);

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "DB Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
