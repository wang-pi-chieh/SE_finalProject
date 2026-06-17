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

if (empty($name) || empty($amount) || empty($deadline)) {
    echo json_encode(["success" => false, "message" => "請填寫必要欄位"]);
    exit;
}

// 啟動 Session，用來取得目前登入者角色
session_start();
$session_username = $_SESSION['username'] ?? 'admin';
$session_role = $_SESSION['role'] ?? '';

// 1. 先以表單選擇的「負責單位」為主
$provider_username = !empty($requested_provider) ? $requested_provider : $session_username;

// 檢查這個 provider 是否是合法的獎助單位
$checkUnit = $conn->prepare("SELECT username FROM scholarship_units WHERE username = ?");
$checkUnit->bind_param("s", $provider_username);
$checkUnit->execute();
$unitExists = $checkUnit->get_result()->num_rows > 0;
$checkUnit->close();

if (!$unitExists) {
    // 2. 如果選到的 provider 不是獎助單位，再看登入者是不是系統管理員
    $checkSysAdmin = $conn->prepare("SELECT username FROM system_admins WHERE username = ?");
    $checkSysAdmin->bind_param("s", $session_username);
    $checkSysAdmin->execute();
    $isSysAdmin = $checkSysAdmin->get_result()->num_rows > 0;
    $checkSysAdmin->close();

    if ($isSysAdmin || $session_role === 'system_admin' || $session_role === '系統管理員' || $session_role === '系管') {
        // 系統管理員：若沒指定或指定錯誤，就用預設 admin 單位
        $provider_username = 'admin';
    } else {
        echo json_encode(["success" => false, "message" => "您沒有發布獎學金的權限 (非獎助單位)"]);
        exit;
    }
}

$application_start_date = date('Y-m-d'); // Default start date to today

$stmt = $conn->prepare("INSERT INTO scholarships (name, description, amount, quota, application_end_date, application_start_date, provider_username, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
$stmt->bind_param("sssisss", $name, $desc, $amount, $quota, $deadline, $application_start_date, $provider_username);

if ($stmt->execute()) {
    require_once 'log_utils.php';
    // Use the determined provider_username or session username as actor
    $actor = $_SESSION['username'] ?? $provider_username;
    logAction($actor, '新增獎學金', '新增項目: ' . $name . ' (金額: ' . $amount . ')');

    echo json_encode(["success" => true, "id" => $conn->insert_id]);
} else {
    echo json_encode(["success" => false, "message" => "DB Error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>
