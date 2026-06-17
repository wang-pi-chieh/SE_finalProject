<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$draftId = intval($input['draft_id'] ?? 0);
if ($draftId <= 0) {
    echo json_encode(['success' => false, 'message' => '缺少草稿編號'], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->begin_transaction();
try {
    $draftStmt = $conn->prepare("SELECT student_username, scholarship_id, draft_payload FROM application_drafts WHERE id = ? AND status = 'draft' FOR UPDATE");
    $draftStmt->bind_param('i', $draftId);
    $draftStmt->execute();
    $draft = $draftStmt->get_result()->fetch_assoc();
    $draftStmt->close();
    if (!$draft) throw new Exception('找不到可送出的草稿');
    $payload = json_decode($draft['draft_payload'], true) ?: [];

    $student = $draft['student_username'];
    $scholarshipId = intval($draft['scholarship_id']);
    $academicYear = trim($payload['academic_year'] ?? '');
    $semester = trim($payload['semester'] ?? '');
    $phone = trim($payload['phone'] ?? '');
    $email = trim($payload['email'] ?? '');
    $bank = trim($payload['bank_account'] ?? '');
    $familyHousing = trim($payload['family_housing_status'] ?? '');
    $personalHousing = trim($payload['personal_housing_status'] ?? '');
    $loan = trim($payload['has_student_loan'] ?? '');
    $waiver = trim($payload['tuition_waiver'] ?? '');
    $familySituation = trim($payload['family_situation_desc'] ?? '');
    $familyMembers = trim($payload['family_members_desc'] ?? '');
    $referrerUsername = trim($payload['referrer_username'] ?? '');
    $recommendationRequired = $referrerUsername !== '' ? 1 : 0;

    $provider = null;
    $providerStmt = $conn->prepare("SELECT provider_username FROM scholarships WHERE id = ?");
    $providerStmt->bind_param('i', $scholarshipId);
    $providerStmt->execute();
    $providerRow = $providerStmt->get_result()->fetch_assoc();
    $provider = $providerRow['provider_username'] ?? null;
    $providerStmt->close();

    $insert = $conn->prepare("INSERT INTO applications (student_username, scholarship_id, academic_year, semester, phone, email, bank_account, family_housing_status, personal_housing_status, has_student_loan, tuition_waiver, family_situation_desc, family_members_desc, referrer_username, recommendation_required, reviewed_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 3)");
    $insert->bind_param('sissssssssssssis', $student, $scholarshipId, $academicYear, $semester, $phone, $email, $bank, $familyHousing, $personalHousing, $loan, $waiver, $familySituation, $familyMembers, $referrerUsername, $recommendationRequired, $provider);
    if (!$insert->execute()) throw new Exception($insert->error);
    $applicationId = $insert->insert_id;
    $insert->close();

    $update = $conn->prepare("UPDATE application_drafts SET status = 'submitted', submitted_application_id = ?, updated_at = NOW() WHERE id = ?");
    $update->bind_param('ii', $applicationId, $draftId);
    $update->execute();
    $update->close();

    $conn->commit();
    echo json_encode(['success' => true, 'application_id' => $applicationId, 'message' => '草稿已送出申請'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
