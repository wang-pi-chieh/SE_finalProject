<?php
// 更新既有申請（學生補件或修改資料用）
header('Content-Type: application/json');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
$student_username = $_POST['student_username'] ?? '';

if ($application_id <= 0 || empty($student_username)) {
    echo json_encode(["success" => false, "message" => "缺少必要欄位 (application_id 或 student_username)"], JSON_UNESCAPED_UNICODE);
    exit;
}

// 先抓舊資料，確認權限並取出原本檔案欄位
$checkSql = "SELECT student_username, biography, application_documents FROM applications WHERE id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $application_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "找不到申請資料"], JSON_UNESCAPED_UNICODE);
    exit;
}

$existing = $checkResult->fetch_assoc();

if ($existing['student_username'] !== $student_username) {
    echo json_encode(["success" => false, "message" => "無權限編輯此申請"], JSON_UNESCAPED_UNICODE);
    exit;
}

$existingBio = $existing['biography'] ?? '';
$existingDocs = $existing['application_documents'] ?? '';

// 取得其他欄位
$scholarship_id = $_POST['scholarship_id'] ?? '';
$academic_year = $_POST['academic_year'] ?? '';
$semester = $_POST['semester'] ?? '';

$family_housing = $_POST['family_housing_status'] ?? '';
$personal_housing = $_POST['personal_housing_status'] ?? '';
$loan = $_POST['has_student_loan'] ?? '';
$waiver = $_POST['tuition_waiver'] ?? '';
$prev_scholarship = $_POST['previous_scholarship_name'] ?? '';
$family_situation = $_POST['family_situation_desc'] ?? '';
$family_members = $_POST['family_members_desc'] ?? '';
$referrer_rel = $_POST['referrer_relationship'] ?? '';
$referrer_name = $_POST['referrer_name'] ?? '';
$referrer_username = $_POST['referrer_username'] ?? null;
$recommendation_required = $_POST['recommendation_required'] ?? '0';

// 聯絡與匯款
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$bank_account = $_POST['bank_account'] ?? '';

// 檔案上傳處理（邏輯與 submit_application.php 類似）
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function handleUploadUpdate($fileKey, $prefix, $username, $uploadDir)
{
    if (isset($_FILES[$fileKey]) && is_array($_FILES[$fileKey]['name'])) {
        $paths = [];
        $count = count($_FILES[$fileKey]['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES[$fileKey]['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$fileKey]['name'][$i], PATHINFO_EXTENSION);
                $filename = $prefix . '_' . $username . '_' . time() . '_' . $i . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'][$i], $targetPath)) {
                    $paths[] = $targetPath;
                }
            }
        }
        return empty($paths) ? '' : json_encode($paths, JSON_UNESCAPED_UNICODE);
    } elseif (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . $username . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
            return $targetPath;
        }
    }
    return '';
}

$biographyPath = handleUploadUpdate('biography_file', 'bio', $student_username, $uploadDir);
$otherDocsPath = handleUploadUpdate('other_docs_file', 'other', $student_username, $uploadDir);

// 若沒有重新上傳，保留舊值
if ($biographyPath === '') {
    $biographyPath = $existingBio;
}
if ($otherDocsPath === '') {
    $otherDocsPath = $existingDocs;
}

// 重新送出後，狀態改回「審核中 / 未審查」(3)
$new_status = 3;

$sql = "UPDATE applications SET
            scholarship_id = ?,
            academic_year = ?,
            semester = ?,
            biography = ?,
            application_documents = ?,
            family_housing_status = ?,
            personal_housing_status = ?,
            has_student_loan = ?,
            tuition_waiver = ?,
            previous_scholarship_name = ?,
            family_situation_desc = ?,
            family_members_desc = ?,
            referrer_relationship = ?,
            referrer_name = ?,
            referrer_username = ?,
            recommendation_required = ?,
            phone = ?,
            email = ?,
            bank_account = ?,
            status = ?
        WHERE id = ? AND student_username = ?";

$stmt = $conn->prepare($sql);

// 參數型別：
// 1 scholarship_id (int)
// 2 academic_year (string)
// 3 semester (string)
// 4 biography (string)
// 5 application_documents (string)
// 6 family_housing_status (string)
// 7 personal_housing_status (string)
// 8 has_student_loan (string)
// 9 tuition_waiver (string)
//10 previous_scholarship_name (string)
//11 family_situation_desc (string)
//12 family_members_desc (string)
//13 referrer_relationship (string)
//14 referrer_name (string)
//15 referrer_username (string)
//16 recommendation_required (string)
//17 status (int)
//18 id (int)
//19 student_username (string)
$stmt->bind_param(
    "issssssssssssssisssiis",
    $scholarship_id,
    $academic_year,
    $semester,
    $biographyPath,
    $otherDocsPath,
    $family_housing,
    $personal_housing,
    $loan,
    $waiver,
    $prev_scholarship,
    $family_situation,
    $family_members,
    $referrer_rel,
    $referrer_name,
    $referrer_username,
    $recommendation_required,
    $phone,
    $email,
    $bank_account,
    $new_status,
    $application_id,
    $student_username
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "申請已更新並重新送出"], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "資料庫錯誤：" . $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();


