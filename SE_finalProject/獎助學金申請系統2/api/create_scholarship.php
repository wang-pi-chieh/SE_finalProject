<?php
// api/create_scholarship.php
header('Content-Type: application/json');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

$name = $_POST['name'] ?? '';
$amount = $_POST['amount'] ?? '';
$quota = $_POST['quota'] ?? 0;
$deadline = $_POST['deadline'] ?? '';
$desc = $_POST['description'] ?? '';
$requested_provider = $_POST['provider_username'] ?? '';
$requested_provider = trim((string) $requested_provider);

if (empty($name) || empty($amount) || empty($deadline)) {
    echo json_encode(["success" => false, "message" => "請填寫必要欄位"]);
    exit;
}

// 啟動 Session，用來取得目前登入者角色
session_start();
$session_username = $_SESSION['username'] ?? 'admin';
$session_role = $_SESSION['role'] ?? '';

$is_system_admin = false;
$checkSysAdmin = $conn->prepare("SELECT username FROM system_admins WHERE username = ?");
if ($checkSysAdmin) {
    $checkSysAdmin->bind_param("s", $session_username);
    $checkSysAdmin->execute();
    $is_system_admin = $checkSysAdmin->get_result()->num_rows > 0;
    $checkSysAdmin->close();
}
$is_system_admin = $is_system_admin || in_array($session_role, ['system_admin', '系統管理員', '系管'], true);

// 系統管理員可以替任一獎助單位新增，獎助單位只能以自己為發布單位。
if ($requested_provider !== '') {
    $provider_username = $requested_provider;
} elseif ($is_system_admin) {
    echo json_encode(["success" => false, "message" => "請選擇負責單位"]);
    exit;
} else {
    $provider_username = $session_username;
}

// 檢查這個 provider 是否是合法的獎助單位
$checkUnit = $conn->prepare("SELECT username FROM scholarship_units WHERE username = ?");
$checkUnit->bind_param("s", $provider_username);
$checkUnit->execute();
$unitExists = $checkUnit->get_result()->num_rows > 0;
$checkUnit->close();

if (!$unitExists) {
    echo json_encode(["success" => false, "message" => "選擇的負責單位不存在，請重新整理後再試"]);
    exit;
}

if (!$is_system_admin && $provider_username !== $session_username) {
    echo json_encode(["success" => false, "message" => "您只能以自己的獎助單位發布獎學金"]);
    exit;
}

$application_start_date = date('Y-m-d'); // Default start date to today

$stmt = $conn->prepare("INSERT INTO scholarships (name, description, amount, quota, application_end_date, application_start_date, provider_username, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
$stmt->bind_param("sssisss", $name, $desc, $amount, $quota, $deadline, $application_start_date, $provider_username);

if ($stmt->execute()) {
    require_once 'log_utils.php';
    // Use the determined provider_username or session username as actor
    $actor = $_SESSION['username'] ?? $provider_username;
    logAction($actor, '新增獎學金', "新增項目: $name (金額: $$amount)");

    echo json_encode(["success" => true, "id" => $conn->insert_id]);
} else {
    echo json_encode(["success" => false, "message" => "新增失敗，請確認資料格式與負責單位後再試"]);
}

$stmt->close();
$conn->close();
?>
