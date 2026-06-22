<?php
// api/register.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';
require_once 'auth_password.php';

// 讀取前端傳來的 JSON 資料
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$username = $data['username'] ?? '';
$role = $data['role'] ?? '';
$department = $data['department'] ?? '';
if ($department === 'NONE') {
    $department = '';
}
$real_name = $data['real_name'] ?? '';
$password = $data['password'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';
$contact_person = $data['contact_person'] ?? '';

// 檢查必填欄位
if (empty($username) || empty($password) || empty($role)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// 驗證：系所欄位僅限學生與老師
if (($role === '系管' || $role === '獎助單位') && !empty($department)) {
    echo json_encode(["success" => false, "message" => "系所欄位僅限學生與老師填寫！"]);
    exit;
}

// 驗證：學生與老師必須填寫系所
if (($role === '學生' || $role === '老師') && empty($department)) {
    echo json_encode(["success" => false, "message" => "老師、學生須填寫系所！"]);
    exit;
}

// 驗證：獎助單位必須填寫聯絡人
if ($role === '獎助單位' && empty($contact_person)) {
    echo json_encode(["success" => false, "message" => "獎助單位須填寫聯絡人姓名！"]);
    exit;
}

// 1. 插入 users 主表
try {
    auth_password_ensure_column($conn);
} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "密碼欄位初始化失敗"]);
    exit;
}

$password_hash = auth_password_hash($password);
$sqlUser = "INSERT INTO users (username, role, real_name, password, phone, email) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sqlUser);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("ssssss", $username, $role, $real_name, $password_hash, $phone, $email);

if ($stmt->execute()) {
    // 主表插入成功，接著處理子表
    $sqlSub = "";
    $stmtSub = null;
    $subParamTypes = "";
    $subParams = [];

    switch ($role) {
        case '學生':
            $sqlSub = "INSERT INTO students (username, department) VALUES (?, ?)";
            $subParamTypes = "ss";
            $subParams = [$username, $department];
            break;
        case '老師':
            $sqlSub = "INSERT INTO teachers (username, department) VALUES (?, ?)";
            $subParamTypes = "ss";
            $subParams = [$username, $department];
            break;
        case '系管':
        case '系統管理員':
            $sqlSub = "INSERT INTO system_admins (username) VALUES (?)";
            $subParamTypes = "s";
            $subParams = [$username];
            break;
        case '獎助單位':
            // real_name 在此視為「獎助單位名稱」，同時寫入 scholarship_units.unit_name
            $sqlSub = "INSERT INTO scholarship_units (username, unit_name, person_in_charge) VALUES (?, ?, ?)";
            $subParamTypes = "sss";
            $subParams = [$username, $real_name, $contact_person];
            break;
    }

    if ($sqlSub) {
        $stmtSub = $conn->prepare($sqlSub);

        if ($stmtSub && $subParamTypes && !empty($subParams)) {
            $stmtSub->bind_param($subParamTypes, ...$subParams);
        }

        if (!$stmtSub || !$stmtSub->execute()) {
            // 子表建立失敗，但主表已成功。實際運作可能需要 Transaction 回滾，這裡先簡單回報
            echo json_encode(["success" => true, "message" => "註冊成功! (但詳細資料表建立異常)"]);
        } else {
            echo json_encode(["success" => true, "message" => "註冊成功!"]);
        }
        $stmtSub->close();
    } else {
        // 沒有對應的角色子表，或預設角色
        echo json_encode(["success" => true, "message" => "註冊成功! (僅建立使用者帳號)"]);
    }
} else {
    // 主表插入失敗
    // 若為帳號或 Email 重複，回傳較友善的訊息
    if ($stmt->errno === 1062) {
        echo json_encode(["success" => false, "message" => "已註冊過：此帳號或 Email 已被使用。"]);
    } else {
        echo json_encode(["success" => false, "message" => "註冊失敗: " . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>
