<?php
// 取得學生用來編輯的申請資料（回傳原始欄位）
header('Content-Type: application/json');
require 'db_connect.php';

$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student_username = $_GET['student_username'] ?? '';

if ($application_id <= 0 || empty($student_username)) {
    echo json_encode([
        "success" => false,
        "message" => "缺少必要參數"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "SELECT 
                id,
                student_username,
                scholarship_id,
                academic_year,
                semester,
                phone,
                email,
                bank_account,
                biography,
                application_documents,
                family_housing_status,
                personal_housing_status,
                has_student_loan,
                tuition_waiver,
                previous_scholarship_name,
                family_situation_desc,
                family_members_desc,
                referrer_relationship,
                referrer_name,
                referrer_username,
                recommendation_required,
                review_comment,
                status
            FROM applications
            WHERE id = ? AND student_username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $application_id, $student_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "找不到對應的申請資料"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $application = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "application" => $application
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "伺服器錯誤：" . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


