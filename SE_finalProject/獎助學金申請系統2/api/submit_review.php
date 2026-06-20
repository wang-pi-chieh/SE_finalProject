<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once __DIR__ . '/reviewer/_review_award_common.php';
session_start();

// 1. Check Authentication (Reviewer/Admin only)
// For now, we assume if you are logged in, you can review, or we should check role.
// Adjust role check as needed. 
/*
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'scholarship_unit') {
   // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
   // exit;
}
*/
// 2. Get Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => '輸入格式錯誤']);
    exit;
}

$application_id = $input['application_id'] ?? null;
$status_text = $input['status'] ?? null; // 'approved' or 'rejected'
$comment = $input['comment'] ?? '';
$score_input = $input['score'] ?? null;
$stage = isset($input['stage']) ? trim((string) $input['stage']) : 'initial';
$is_draft = isset($input['is_draft']) && (string) $input['is_draft'] === '1';
$reviewer_username = trim((string) ($input['reviewer_username'] ?? ($_SESSION['username'] ?? '')));

if (!$application_id || (!$is_draft && !isset($status_text))) {
    echo json_encode(['success' => false, 'message' => '缺少必要欄位：application_id 或審查狀態']);
    exit;
}

if ($reviewer_username === '') {
    echo json_encode(['success' => false, 'message' => '缺少 reviewer_username，無法確認審查權限']);
    exit;
}

$allowed_stages = ['initial', 'second', 'final', 'supplement', 'draft'];
if (!in_array($stage, $allowed_stages, true)) {
    echo json_encode(['success' => false, 'message' => '審查階段不正確']);
    exit;
}

$score = null;
if ($score_input !== null && $score_input !== '') {
    if (!is_numeric($score_input)) {
        echo json_encode(['success' => false, 'message' => '評分必須是 0 到 100 的數字']);
        exit;
    }
    $score = round((float) $score_input, 2);
    if ($score < 0 || $score > 100) {
        echo json_encode(['success' => false, 'message' => '評分必須介於 0 到 100']);
        exit;
    }
}

if (!$is_draft && $score === null) {
    echo json_encode(['success' => false, 'message' => '請輸入審查評分']);
    exit;
}
$db_status = $is_draft ? 3 : reviewer_award_normalize_status($status_text);
$numeric_result = (string) $db_status;

$conn->begin_transaction();

try {
    reviewer_award_ensure_schema($conn);

    $actor = reviewer_award_actor($conn, $reviewer_username);
    if (!$actor) {
        throw new Exception('找不到審查人員帳號');
    }

    $application = reviewer_award_fetch_application($conn, (int) $application_id, $actor);
    if (!$application) {
        throw new Exception('找不到可審查的申請或權限不足');
    }

    $stmt = $conn->prepare(
        "UPDATE applications
         SET status = ?, review_comment = ?, review_score = ?, reviewed_at = NOW(), reviewed_by = ?
         WHERE id = ?"
    );
    $stmt->bind_param("isdsi", $db_status, $comment, $score, $reviewer_username, $application_id);

    if (!$stmt->execute()) {
        throw new Exception("更新申請審查狀態失敗：" . $stmt->error);
    }

    $record_stage = $is_draft ? 'draft' : $stage;
    if ($is_draft) {
        $draft_lookup = $conn->prepare(
            "SELECT id
             FROM review_records
             WHERE application_id = ? AND stage = 'draft' AND admin_username = ?
             ORDER BY id DESC
             LIMIT 1"
        );
        if (!$draft_lookup) {
            throw new Exception("準備讀取審查草稿失敗：" . $conn->error);
        }
        $draft_lookup->bind_param("is", $application_id, $reviewer_username);
        $draft_lookup->execute();
        $draft_row = $draft_lookup->get_result()->fetch_assoc();
        $draft_lookup->close();

        if ($draft_row) {
            $draft_id = (int) $draft_row['id'];
            $stmt_hist = $conn->prepare(
                "UPDATE review_records
                 SET review_date = CURRENT_DATE, result = ?, note = ?, score = ?, stage = 'draft', admin_username = ?
                 WHERE id = ?"
            );
            if (!$stmt_hist) {
                throw new Exception("準備更新審查草稿失敗：" . $conn->error);
            }
            $stmt_hist->bind_param("ssdsi", $numeric_result, $comment, $score, $reviewer_username, $draft_id);
        } else {
            $stmt_hist = $conn->prepare(
                "INSERT INTO review_records
                    (application_id, review_date, result, note, score, stage, admin_username)
                 VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?)"
            );
            if (!$stmt_hist) {
                throw new Exception("準備新增審查草稿失敗：" . $conn->error);
            }
            $stmt_hist->bind_param("issdss", $application_id, $numeric_result, $comment, $score, $record_stage, $reviewer_username);
        }
    } else {
        $stmt_hist = $conn->prepare(
            "INSERT INTO review_records
                (application_id, review_date, result, note, score, stage, admin_username)
             VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?)"
        );
        if (!$stmt_hist) {
            throw new Exception("準備新增審查紀錄失敗：" . $conn->error);
        }
        $stmt_hist->bind_param("issdss", $application_id, $numeric_result, $comment, $score, $record_stage, $reviewer_username);
    }

    if (!$stmt_hist->execute()) {
        throw new Exception("寫入審查紀錄失敗：" . $stmt_hist->error);
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => $is_draft ? '審查草稿已儲存' : '審查已送出',
        'score' => $score,
        'stage' => $record_stage
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
