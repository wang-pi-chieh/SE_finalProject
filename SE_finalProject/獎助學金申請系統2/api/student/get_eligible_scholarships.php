<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/matching_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    wu_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$studentUsername = isset($_GET['student_username']) ? mb_substr(trim((string) $_GET['student_username']), 0, 50) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 6;
$limit = max(1, min(20, $limit));

wu_ensure_matching_schema($conn);
wu_validate_student_username($conn, $studentUsername);

$scholarships = wu_get_eligible_scholarships($conn, $studentUsername, $limit);
wu_json_response([
    'success' => true,
    'data' => $scholarships,
]);
?>
