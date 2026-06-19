<?php
require_once __DIR__ . '/_scholarship_import_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chen_json_response(false, 'Invalid method', [], 405);
}

chen_ensure_import_schema($conn);
$confirmedBy = chen_require_admin($conn);

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '{}', true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

$batchId = (int) ($input['batch_id'] ?? 0);
if ($batchId <= 0) {
    chen_json_response(false, '缺少匯入批次編號', [], 400);
}

$createAnnouncement = !empty($input['create_announcement']);

$stmt = $conn->prepare('SELECT * FROM scholarship_import_batches WHERE id = ? LIMIT 1');
if (!$stmt) {
    chen_json_response(false, '讀取匯入批次失敗：' . $conn->error, [], 500);
}
$stmt->bind_param('i', $batchId);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$batch) {
    chen_json_response(false, '找不到匯入批次', [], 404);
}
if ($batch['status'] !== 'uploaded') {
    chen_json_response(false, '此匯入批次已處理，不能重複確認', [], 409);
}

$rows = json_decode((string) $batch['import_data'], true);
if (!is_array($rows)) {
    chen_json_response(false, '匯入資料格式錯誤，請重新上傳 CSV', [], 500);
}

$validRows = array_values(array_filter($rows, static fn($row) => !empty($row['valid'])));
if (empty($validRows)) {
    chen_json_response(false, '沒有有效資料可匯入', [], 400);
}

$inserted = 0;
$conn->begin_transaction();

try {
    $insert = $conn->prepare(
        'INSERT INTO scholarships
         (name, provider_username, description, amount, quota, application_start_date, application_end_date, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$insert) {
        throw new RuntimeException('準備匯入 SQL 失敗：' . $conn->error);
    }

    foreach ($validRows as $row) {
        $data = $row['data'];
        $name = (string) $data['name'];
        $provider = (string) $data['provider_username'];
        $description = (string) ($data['description'] ?? '');
        $amount = (string) $data['amount'];
        $quota = (int) $data['quota'];
        $startDate = (string) $data['application_start_date'];
        $endDate = (string) $data['application_end_date'];
        $isActive = (int) $data['is_active'];

        $insert->bind_param('ssssissi', $name, $provider, $description, $amount, $quota, $startDate, $endDate, $isActive);
        if (!$insert->execute()) {
            throw new RuntimeException("第 {$row['line']} 列匯入失敗：" . $insert->error);
        }
        $inserted++;
    }
    $insert->close();

    if ($createAnnouncement) {
        $title = '新獎學金項目已匯入';
        $content = "管理員已匯入 {$inserted} 筆獎學金項目，請至獎學金列表查看最新申請資訊。";
        $displayDate = date('Y-m-d');
        $statusLabel = '公告';
        $statusType = 'notice';
        $announce = $conn->prepare(
            'INSERT INTO homepage_announcements (title, content, display_date, status_label, status_type, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        if ($announce) {
            $announce->bind_param('sssss', $title, $content, $displayDate, $statusLabel, $statusType);
            $announce->execute();
            $announce->close();
        }
    }

    $update = $conn->prepare(
        "UPDATE scholarship_import_batches
         SET status = 'confirmed', confirmed_at = NOW(), error_report = ?
         WHERE id = ?"
    );
    if (!$update) {
        throw new RuntimeException('更新匯入批次狀態失敗：' . $conn->error);
    }
    $summary = json_encode([
        'inserted_rows' => $inserted,
        'confirmed_by' => $confirmedBy,
        'created_announcement' => $createAnnouncement,
    ], JSON_UNESCAPED_UNICODE);
    $update->bind_param('si', $summary, $batchId);
    if (!$update->execute()) {
        throw new RuntimeException('更新匯入批次狀態失敗：' . $update->error);
    }
    $update->close();

    chen_write_system_log($conn, $confirmedBy, '確認獎學金匯入', "批次 #{$batchId} 匯入 {$inserted} 筆獎學金");
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();

    $error = $e->getMessage();
    $failed = $conn->prepare("UPDATE scholarship_import_batches SET status = 'failed', error_report = ? WHERE id = ?");
    if ($failed) {
        $failedReport = json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
        $failed->bind_param('si', $failedReport, $batchId);
        $failed->execute();
        $failed->close();
    }

    chen_json_response(false, $error, [], 500);
}

chen_json_response(true, '匯入完成', [
    'data' => [
        'batch_id' => $batchId,
        'inserted_rows' => $inserted,
    ],
]);
?>
