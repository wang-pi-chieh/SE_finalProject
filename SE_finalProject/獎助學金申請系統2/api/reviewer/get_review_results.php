<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_review_award_common.php';

$providerUsername = trim((string) ($_GET['provider_username'] ?? ''));
$scholarshipId = isset($_GET['scholarship_id']) ? (int) $_GET['scholarship_id'] : 0;

if ($providerUsername === '') {
    reviewer_award_json(['success' => false, 'message' => '缺少 provider_username'], 400);
}

try {
    reviewer_award_ensure_schema($conn);
    $actor = reviewer_award_actor($conn, $providerUsername);
    if (!$actor) {
        reviewer_award_json(['success' => false, 'message' => '找不到使用者'], 404);
    }

    $scope = reviewer_award_scope($actor, 's');
    $types = $scope['types'];
    $params = $scope['params'];
    $scholarshipFilter = '';
    if ($scholarshipId > 0) {
        $scholarshipFilter = ' AND s.id = ?';
        $types .= 'i';
        $params[] = $scholarshipId;
    }

    $sql = "
        SELECT
            a.id AS application_id,
            a.student_username,
            COALESCE(u.real_name, a.student_username) AS student_name,
            s.id AS scholarship_id,
            s.name AS scholarship_name,
            a.status,
            a.review_score,
            a.reviewed_at,
            a.reviewed_by,
            COUNT(rr.id) AS review_stage_count,
            ROUND(AVG(rr.score), 2) AS integrated_score,
            GROUP_CONCAT(
                CONCAT(rr.stage, ':', COALESCE(rr.score, ''), ':', COALESCE(rr.result, ''))
                ORDER BY rr.created_at ASC, rr.id ASC
                SEPARATOR '|'
            ) AS history_raw
        FROM applications a
        INNER JOIN scholarships s ON s.id = a.scholarship_id
        LEFT JOIN users u ON u.username = a.student_username
        LEFT JOIN review_records rr ON rr.application_id = a.id
        WHERE 1=1 {$scope['sql']} {$scholarshipFilter}
        GROUP BY a.id, a.student_username, u.real_name, s.id, s.name, a.status, a.review_score, a.reviewed_at, a.reviewed_by
        ORDER BY s.id ASC, integrated_score DESC, a.reviewed_at DESC, a.id DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('準備審查整合查詢失敗：' . $conn->error);
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $history = [];
        foreach (array_filter(explode('|', (string) ($row['history_raw'] ?? ''))) as $item) {
            [$stage, $score, $resultText] = array_pad(explode(':', $item, 3), 3, '');
            $history[] = [
                'stage' => $stage,
                'stage_label' => reviewer_award_stage_label($stage),
                'score' => $score === '' ? null : (float) $score,
                'result' => $resultText,
            ];
        }
        $rows[] = [
            'application_id' => (int) $row['application_id'],
            'student_username' => $row['student_username'],
            'student_name' => $row['student_name'],
            'scholarship_id' => (int) $row['scholarship_id'],
            'scholarship_name' => $row['scholarship_name'],
            'status' => (int) $row['status'],
            'review_score' => $row['review_score'] !== null ? (float) $row['review_score'] : null,
            'integrated_score' => $row['integrated_score'] !== null ? (float) $row['integrated_score'] : null,
            'review_stage_count' => (int) $row['review_stage_count'],
            'reviewed_at' => $row['reviewed_at'],
            'reviewed_by' => $row['reviewed_by'],
            'history' => $history,
        ];
    }

    reviewer_award_json(['success' => true, 'data' => $rows, 'count' => count($rows)]);
} catch (Throwable $e) {
    reviewer_award_json(['success' => false, 'message' => $e->getMessage()], 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
