<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_disbursement_common.php';

$providerUsername = trim((string) ($_GET['provider_username'] ?? ''));
if ($providerUsername === '') {
    reviewer_disbursement_json(['success' => false, 'message' => '缺少 provider_username'], 400);
}

try {
    $actor = reviewer_disbursement_actor($conn, $providerUsername);
    if (!$actor) {
        reviewer_disbursement_json(['success' => false, 'message' => '找不到使用者'], 404);
    }

    reviewer_disbursement_ensure_table($conn);
    reviewer_disbursement_sync_approved($conn, $actor);

    $types = '';
    $params = [];
    $scope = reviewer_disbursement_scope($actor, $types, $params);
    $sql = "
        SELECT
            d.id,
            d.application_id,
            d.status,
            d.handled_by,
            d.handled_at,
            d.note,
            d.created_at,
            d.updated_at,
            a.student_username,
            a.scholarship_id,
            COALESCE(u.real_name, a.student_username) AS student_name,
            st.department,
            s.name AS scholarship_name,
            s.amount,
            s.provider_username
        FROM award_disbursements d
        INNER JOIN applications a ON a.id = d.application_id
        INNER JOIN scholarships s ON s.id = a.scholarship_id
        LEFT JOIN users u ON u.username = a.student_username
        LEFT JOIN students st ON st.username = a.student_username
        WHERE a.status = 1 {$scope}
        ORDER BY FIELD(d.status, 'pending', 'failed', 'paid'), a.application_date DESC, d.updated_at DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('準備撥款查詢失敗：' . $conn->error);
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id' => (int) $row['id'],
            'application_id' => (int) $row['application_id'],
            'student_username' => $row['student_username'],
            'student_name' => $row['student_name'],
            'department' => $row['department'],
            'scholarship_id' => (int) $row['scholarship_id'],
            'scholarship_name' => $row['scholarship_name'],
            'amount' => $row['amount'],
            'status' => $row['status'],
            'handled_by' => $row['handled_by'],
            'handled_at' => $row['handled_at'],
            'note' => $row['note'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    reviewer_disbursement_json(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    reviewer_disbursement_json(['success' => false, 'message' => $e->getMessage()], 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
