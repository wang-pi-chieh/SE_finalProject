<?php
// api/submit_application.php
header('Content-Type: application/json');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// 1. 取得基本欄位 (從 $_POST)
$student_username = $_POST['student_username'] ?? '';
$scholarship_id = $_POST['scholarship_id'] ?? '';
$academic_year = $_POST['academic_year'] ?? ''; // e.g., 112
$semester = $_POST['semester'] ?? ''; // e.g., 下

// 聯絡與匯款
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$bank_account = $_POST['bank_account'] ?? '';

// 其他欄位
$family_housing = $_POST['family_housing_status'] ?? '';
$personal_housing = $_POST['personal_housing_status'] ?? '';
$loan = $_POST['has_student_loan'] ?? '';
$waiver = $_POST['tuition_waiver'] ?? '';
$prev_scholarship = $_POST['previous_scholarship_name'] ?? '';
$family_situation = $_POST['family_situation_desc'] ?? '';
$family_members = $_POST['family_members_desc'] ?? '';
$referrer_rel = $_POST['referrer_relationship'] ?? '';
$referrer_name = $_POST['referrer_name'] ?? '';
$referrer_username = $_POST['referrer_username'] ?? null; // ID of the professor

// 取得此獎學金所屬的獎助單位帳號，作為預設的 reviewed_by（指派給哪個單位負責）
$default_reviewer = null;
if (!empty($scholarship_id)) {
    $provSql = "SELECT provider_username FROM scholarships WHERE id = ? LIMIT 1";
    if ($provStmt = $conn->prepare($provSql)) {
        $provStmt->bind_param("i", $scholarship_id);
        if ($provStmt->execute()) {
            $provRes = $provStmt->get_result();
            if ($provRow = $provRes->fetch_assoc()) {
                $default_reviewer = $provRow['provider_username'] ?? null;
            }
        }
        $provStmt->close();
    }
}

// 驗證必填
if (empty($student_username) || empty($scholarship_id)) {
    echo json_encode(["success" => false, "message" => "Missing required fields (student or scholarship)"]);
    exit;
}

// 避免同一學生在同一學年學期、同一獎學金重複申請
if (!empty($academic_year) && !empty($semester)) {
    $dupSql = "SELECT id FROM applications 
               WHERE student_username = ? 
                 AND scholarship_id = ? 
                 AND academic_year = ? 
                 AND semester = ? 
               LIMIT 1";
    $dupStmt = $conn->prepare($dupSql);
    $dupStmt->bind_param("siis", $student_username, $scholarship_id, $academic_year, $semester);
    $dupStmt->execute();
    $dupResult = $dupStmt->get_result();
    if ($dupResult && $dupResult->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "您已在本學期申請過此獎學金，不可重複申請。"]);
        $dupStmt->close();
        $conn->close();
        exit;
    }
    $dupStmt->close();
}

// 處理檔案上傳
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Helper to handle upload (Single or Array)
function handleUpload($fileKey, $prefix, $username, $uploadDir)
{
    // Check if it's an array upload (multiple files)
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
    }
    // Single file upload
    elseif (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . $username . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
            return $targetPath;
        }
    }
    return '';
}

// biography 存的是檔案路徑 (對應 "自傳/讀書計畫") - Single
$biographyPath = handleUpload('biography_file', 'bio', $student_username, $uploadDir);
// application_documents 存的是檔案路徑 (對應 "其他有利審查資料") - Multiple supported
$otherDocsPath = handleUpload('other_docs_file', 'other', $student_username, $uploadDir);


// 決定初始狀態
$recommendation_required = isset($_POST['recommendation_required']) && $_POST['recommendation_required'] === '1' ? 1 : 0;
// status: 3 (Pending/未審查)
$initial_status = 3;

// 若需要推薦信，檢查是否已選教授
if ($recommendation_required && empty($referrer_username)) {
    echo json_encode(["success" => false, "message" => "Recommendation is required, please select a professor."]);
    exit;
}

// 3. 寫入資料庫
// 多寫入一個 reviewed_by，預設為該獎學金的 provider_username，避免欄位為 NULL
$sql = "INSERT INTO applications (
    student_username, scholarship_id, academic_year, semester,
    phone, email, bank_account,
    biography, application_documents, 
    family_housing_status, personal_housing_status, has_student_loan, tuition_waiver,
    previous_scholarship_name, family_situation_desc, family_members_desc, referrer_relationship, referrer_name, referrer_username,
    recommendation_required, reviewed_by, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sisssssssssssssssssssi",
    $student_username,
    $scholarship_id,
    $academic_year,
    $semester,
    $phone,
    $email,
    $bank_account,
    $biographyPath,       // 自傳檔案路徑
    $otherDocsPath,       // 其他文件檔案路徑
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
    $default_reviewer,
    $initial_status
);

if ($stmt->execute()) {
    $application_id = $stmt->insert_id;
    echo json_encode(["success" => true, "message" => "Application submitted successfully", "application_id" => $application_id]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>