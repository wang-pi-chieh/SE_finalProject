<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$username = $_GET['username'] ?? '';

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Missing username']);
    exit;
}

try {
    // 1. 基本使用者資料
    $stmt = $conn->prepare("SELECT username, real_name, email, phone, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user = $result->fetch_assoc();
    $role = $user['role'];

    // 2. 依角色補上註冊時填寫的其他資料
    $extra = [
        'department' => null,
        'unit_name' => null,
        'person_in_charge' => null
    ];

    // 學生 / 老師：從 students / teachers 表抓系所
    if ($role === '學生') {
        $stmt2 = $conn->prepare("SELECT department FROM students WHERE username = ? LIMIT 1");
        $stmt2->bind_param("s", $username);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($row = $res2->fetch_assoc()) {
            $extra['department'] = $row['department'];
        }
        $stmt2->close();
    } elseif ($role === '老師') {
        $stmt2 = $conn->prepare("SELECT department FROM teachers WHERE username = ? LIMIT 1");
        $stmt2->bind_param("s", $username);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($row = $res2->fetch_assoc()) {
            $extra['department'] = $row['department'];
        }
        $stmt2->close();
    } elseif ($role === '獎助單位') {
        // 獎助單位：抓獎助單位名稱與聯絡人
        $stmt2 = $conn->prepare("SELECT unit_name, person_in_charge FROM scholarship_units WHERE username = ? LIMIT 1");
        $stmt2->bind_param("s", $username);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($row = $res2->fetch_assoc()) {
            $extra['unit_name'] = $row['unit_name'];
            $extra['person_in_charge'] = $row['person_in_charge'];
        }
        $stmt2->close();
    }

    $data = array_merge($user, $extra);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();

