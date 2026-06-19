<?php
// api/student/matching_utils.php
// Shared helpers for eligible-scholarship matching and notification sync.
require_once __DIR__ . '/../common/mailer.php';

function wu_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function wu_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        wu_json_response(['success' => false, 'message' => 'Input invalid: JSON 格式錯誤'], 400);
    }
    return $data;
}

function wu_table_exists(mysqli $conn, string $tableName): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $exists;
}

function wu_column_exists(mysqli $conn, string $tableName, string $columnName): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $tableName, $columnName);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $exists;
}

function wu_ensure_matching_schema(mysqli $conn): void
{
    $notificationSql = "
        CREATE TABLE IF NOT EXISTS student_notifications (
          id int(11) NOT NULL AUTO_INCREMENT,
          student_username varchar(50) NOT NULL,
          type varchar(50) NOT NULL,
          title varchar(255) NOT NULL,
          message text NOT NULL,
          related_application_id int(11) DEFAULT NULL,
          related_scholarship_id int(11) DEFAULT NULL,
          dedup_key varchar(255) NOT NULL,
          is_read tinyint(1) NOT NULL DEFAULT 0,
          email_sent_at datetime DEFAULT NULL,
          email_last_error varchar(255) DEFAULT NULL,
          created_at timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (id),
          UNIQUE KEY uniq_student_notification_dedup (dedup_key),
          KEY idx_student_notifications_user_read (student_username, is_read, created_at),
          KEY idx_student_notifications_application (related_application_id),
          KEY idx_student_notifications_scholarship (related_scholarship_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    if (!$conn->query($notificationSql)) {
        wu_json_response(['success' => false, 'message' => 'Database connection failed: 無法建立學生通知資料表'], 500);
    }

    if (!wu_column_exists($conn, 'student_notifications', 'email_sent_at')) {
        if (!$conn->query("ALTER TABLE student_notifications ADD COLUMN email_sent_at datetime DEFAULT NULL AFTER is_read")) {
            wu_json_response(['success' => false, 'message' => 'Database connection failed: 無法補上通知 Email 欄位'], 500);
        }
    }
    if (!wu_column_exists($conn, 'student_notifications', 'email_last_error')) {
        if (!$conn->query("ALTER TABLE student_notifications ADD COLUMN email_last_error varchar(255) DEFAULT NULL AFTER email_sent_at")) {
            wu_json_response(['success' => false, 'message' => 'Database connection failed: 無法補上通知錯誤欄位'], 500);
        }
    }

    $ruleSql = "
        CREATE TABLE IF NOT EXISTS scholarship_eligibility_rules (
          scholarship_id int(11) NOT NULL,
          min_gpa decimal(4,2) DEFAULT NULL,
          min_avg_score decimal(5,2) DEFAULT NULL,
          max_class_rank_percent decimal(5,2) DEFAULT NULL,
          allowed_departments text DEFAULT NULL,
          provider_department varchar(100) DEFAULT NULL,
          notes varchar(255) DEFAULT NULL,
          PRIMARY KEY (scholarship_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    if (!$conn->query($ruleSql)) {
        wu_json_response(['success' => false, 'message' => 'Database connection failed: 無法建立資格比對規則資料表'], 500);
    }

    wu_seed_default_eligibility_rules($conn);
}

function wu_seed_default_eligibility_rules(mysqli $conn): void
{
    $rules = [
        [1, 3.50, 85.00, null, null, null, '提供予家境清寒且學業成績優異之學生'],
        [2, null, null, 10.00, '["資訊工程學系"]', '資訊工程學系', '限各系學生申請，學業成績需達班排前 10%'],
        [3, 3.80, 90.00, null, null, null, '獎勵發表頂尖期刊論文之學生'],
        [4, 3.00, 80.00, null, null, null, '補助赴海外交換學生之機票與生活費'],
        [5, null, 60.00, null, null, null, '弱勢學生生活津貼，前一學期成績須達 60 分以上'],
    ];

    $existsStmt = $conn->prepare('SELECT id FROM scholarships WHERE id = ? LIMIT 1');
    $upsertStmt = $conn->prepare(
        "INSERT INTO scholarship_eligibility_rules
            (scholarship_id, min_gpa, min_avg_score, max_class_rank_percent, allowed_departments, provider_department, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            min_gpa = VALUES(min_gpa),
            min_avg_score = VALUES(min_avg_score),
            max_class_rank_percent = VALUES(max_class_rank_percent),
            allowed_departments = VALUES(allowed_departments),
            provider_department = VALUES(provider_department),
            notes = VALUES(notes)"
    );
    if (!$existsStmt || !$upsertStmt) {
        return;
    }

    foreach ($rules as $rule) {
        $scholarshipId = (int) $rule[0];
        $existsStmt->bind_param('i', $scholarshipId);
        $existsStmt->execute();
        if (!$existsStmt->get_result()->fetch_assoc()) {
            continue;
        }

        [$id, $minGpa, $minAvg, $maxRank, $departments, $providerDepartment, $notes] = $rule;
        $upsertStmt->bind_param('idddsss', $id, $minGpa, $minAvg, $maxRank, $departments, $providerDepartment, $notes);
        $upsertStmt->execute();
    }

    $existsStmt->close();
    $upsertStmt->close();
}

function wu_validate_student_username(mysqli $conn, string $username): void
{
    if ($username === '') {
        wu_json_response(['success' => false, 'message' => 'Input invalid: 缺少學生帳號'], 400);
    }

    $stmt = $conn->prepare("SELECT u.username, u.role, s.username AS student_username FROM users u LEFT JOIN students s ON s.username = u.username WHERE u.username = ? LIMIT 1");
    if (!$stmt) {
        wu_json_response(['success' => false, 'message' => 'Database connection failed: 無法檢查學生帳號'], 500);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['student_username'])) {
        wu_json_response(['success' => false, 'message' => 'Permission denied: 找不到學生帳號'], 403);
    }

    $role = (string) ($user['role'] ?? '');
    if ($role !== 'student' && $role !== '學生') {
        wu_json_response(['success' => false, 'message' => 'Permission denied: 只有學生可以讀取推薦與通知'], 403);
    }
}

function wu_get_student_email(mysqli $conn, string $username): ?array
{
    $sql = 'SELECT real_name, email FROM users WHERE username = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if (!$result || empty($result['email'])) {
        return null;
    }

    return [
        'real_name' => (string) ($result['real_name'] ?? ''),
        'email' => (string) $result['email'],
    ];
}

function wu_mark_notification_email_status(mysqli $conn, int $notificationId, bool $success, ?string $errorMessage = null): void
{
    if (!wu_column_exists($conn, 'student_notifications', 'email_sent_at')) {
        return;
    }

    $sql = '
        UPDATE student_notifications
        SET email_sent_at = CASE WHEN ? = 1 THEN NOW() ELSE email_sent_at END,
            email_last_error = ?
        WHERE id = ?
    ';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }
    $flag = $success ? 1 : 0;
    $safeError = $success ? null : mb_substr((string) ($errorMessage ?? '寄送失敗'), 0, 255);
    $stmt->bind_param('isi', $flag, $safeError, $notificationId);
    $stmt->execute();
    $stmt->close();
}

function wu_try_send_notification_email(
    mysqli $conn,
    int $notificationId,
    string $username,
    string $title,
    string $message,
    bool $alreadySent
): void {
    if ($alreadySent) {
        return;
    }

    $student = wu_get_student_email($conn, $username);
    if (!$student) {
        wu_mark_notification_email_status($conn, $notificationId, false, '找不到學生 Email');
        return;
    }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $html = "
        <div style=\"font-family:Arial,'Noto Sans TC',sans-serif;line-height:1.6;color:#1f2937;\">
            <h2 style=\"margin:0 0 12px;\">NSAMS 站內通知提醒</h2>
            <p style=\"margin:0 0 8px;\">您好，{$student['real_name']}：</p>
            <p style=\"margin:0 0 8px;\"><strong>{$safeTitle}</strong></p>
            <p style=\"margin:0 0 12px;\">{$safeMessage}</p>
            <p style=\"margin:0;color:#6b7280;font-size:12px;\">此信件由系統自動寄出，請勿直接回覆。</p>
        </div>
    ";

    $error = null;
    $success = wu_send_gmail_notification($student['email'], $student['real_name'], "【NSAMS】{$title}", $html, $error);
    wu_mark_notification_email_status($conn, $notificationId, $success, $error);
}

function wu_get_student_context(mysqli $conn, string $username): ?array
{
    $sql = "
        SELECT
            u.username,
            u.real_name,
            s.department,
            s.grade_level,
            s.class_name
        FROM users u
        LEFT JOIN students s ON s.username = u.username
        WHERE u.username = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        return null;
    }

    $gradeSql = "
        SELECT academic_year, semester, avg_score, gpa, class_rank, class_size
        FROM grades
        WHERE student_username = ?
        ORDER BY academic_year DESC, semester DESC
        LIMIT 1
    ";
    $gradeStmt = $conn->prepare($gradeSql);
    $gradeStmt->bind_param('s', $username);
    $gradeStmt->execute();
    $latestGrade = $gradeStmt->get_result()->fetch_assoc() ?: null;
    $gradeStmt->close();

    $appliedIds = [];
    $appSql = 'SELECT scholarship_id FROM applications WHERE student_username = ?';
    $appStmt = $conn->prepare($appSql);
    $appStmt->bind_param('s', $username);
    $appStmt->execute();
    $appResult = $appStmt->get_result();
    while ($row = $appResult->fetch_assoc()) {
        $appliedIds[] = (int) $row['scholarship_id'];
    }
    $appStmt->close();

    return [
        'student' => $student,
        'latest_grade' => $latestGrade,
        'applied_scholarship_ids' => $appliedIds,
    ];
}

function wu_decode_department_list(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function wu_evaluate_scholarship(array $scholarship, ?array $rule, array $context): array
{
    $student = $context['student'];
    $grade = $context['latest_grade'];
    $appliedIds = $context['applied_scholarship_ids'];
    $scholarshipId = (int) $scholarship['id'];
    $reasons = [];
    $score = 0;

    if ((int) ($scholarship['is_active'] ?? 0) !== 1) {
        return ['eligible' => false, 'reasons' => [], 'score' => 0, 'match_summary' => '未開放'];
    }

    if (in_array($scholarshipId, $appliedIds, true)) {
        return ['eligible' => false, 'reasons' => [], 'score' => 0, 'match_summary' => '已申請'];
    }

    $today = date('Y-m-d');
    if (!empty($scholarship['application_end_date']) && $scholarship['application_end_date'] < $today) {
        return ['eligible' => false, 'reasons' => [], 'score' => 0, 'match_summary' => '已截止'];
    }

    if (!empty($scholarship['application_start_date']) && $scholarship['application_start_date'] > $today) {
        return ['eligible' => false, 'reasons' => [], 'score' => 0, 'match_summary' => '尚未開放'];
    }

    if (!$rule) {
        $reasons[] = '符合基本申請期間';
        return [
            'eligible' => true,
            'reasons' => $reasons,
            'score' => 10,
            'match_summary' => '可申請',
        ];
    }

    $department = trim((string) ($student['department'] ?? ''));
    $allowedDepartments = wu_decode_department_list($rule['allowed_departments'] ?? null);
    if (!empty($allowedDepartments)) {
        if ($department === '' || !in_array($department, $allowedDepartments, true)) {
            return [
                'eligible' => false,
                'reasons' => ["系所不符，需為：" . implode('、', $allowedDepartments)],
                'score' => 0,
                'match_summary' => '系所不符',
            ];
        }
        $reasons[] = "系所符合：{$department}";
        $score += 25;
    } elseif (!empty($rule['provider_department'])) {
        if ($department !== $rule['provider_department']) {
            return [
                'eligible' => false,
                'reasons' => ["系所不符，需為：{$rule['provider_department']}"],
                'score' => 0,
                'match_summary' => '系所不符',
            ];
        }
        $reasons[] = "系所符合：{$department}";
        $score += 25;
    }

    if ($grade) {
        if ($rule['min_gpa'] !== null && $grade['gpa'] !== null) {
            $gpa = (float) $grade['gpa'];
            $minGpa = (float) $rule['min_gpa'];
            if ($gpa < $minGpa) {
                return [
                    'eligible' => false,
                    'reasons' => ["GPA 需達 {$minGpa}，目前為 {$gpa}"],
                    'score' => 0,
                    'match_summary' => 'GPA 不足',
                ];
            }
            $reasons[] = "GPA {$gpa} 達標";
            $score += 20;
        }

        if ($rule['min_avg_score'] !== null && $grade['avg_score'] !== null) {
            $avgScore = (float) $grade['avg_score'];
            $minAvg = (float) $rule['min_avg_score'];
            if ($avgScore < $minAvg) {
                return [
                    'eligible' => false,
                    'reasons' => ["平均成績需達 {$minAvg}，目前為 {$avgScore}"],
                    'score' => 0,
                    'match_summary' => '成績不足',
                ];
            }
            $reasons[] = "平均成績 {$avgScore} 達標";
            $score += 20;
        }

        if ($rule['max_class_rank_percent'] !== null && $grade['class_rank'] !== null && $grade['class_size'] !== null) {
            $classRank = (int) $grade['class_rank'];
            $classSize = max(1, (int) $grade['class_size']);
            $rankPercent = ($classRank / $classSize) * 100;
            $maxPercent = (float) $rule['max_class_rank_percent'];
            if ($rankPercent > $maxPercent) {
                return [
                    'eligible' => false,
                    'reasons' => ["班排需在前 {$maxPercent}%，目前約第 {$classRank}/{$classSize} 名"],
                    'score' => 0,
                    'match_summary' => '班排不符',
                ];
            }
            $reasons[] = "班排 {$classRank}/{$classSize}，符合前 {$maxPercent}%";
            $score += 25;
        }
    } elseif ($rule['min_gpa'] !== null || $rule['min_avg_score'] !== null || $rule['max_class_rank_percent'] !== null) {
        return [
            'eligible' => false,
            'reasons' => ['尚無成績資料，無法比對資格'],
            'score' => 0,
            'match_summary' => '缺少成績',
        ];
    }

    if (!empty($rule['notes'])) {
        $reasons[] = $rule['notes'];
        $score += 10;
    }

    if (empty($reasons)) {
        $reasons[] = '符合基本申請期間';
    }

    return [
        'eligible' => true,
        'reasons' => $reasons,
        'score' => max($score, 10),
        'match_summary' => '符合資格',
    ];
}

function wu_get_eligible_scholarships(mysqli $conn, string $username, int $limit = 6): array
{
    if (!wu_table_exists($conn, 'scholarship_eligibility_rules')) {
        return [];
    }

    $context = wu_get_student_context($conn, $username);
    if (!$context) {
        return [];
    }

    $sql = "
        SELECT s.*, su.unit_name, r.min_gpa, r.min_avg_score, r.max_class_rank_percent,
               r.allowed_departments, r.provider_department, r.notes
        FROM scholarships s
        LEFT JOIN scholarship_units su ON su.username = s.provider_username
        LEFT JOIN scholarship_eligibility_rules r ON r.scholarship_id = s.id
        WHERE s.is_active = 1
        ORDER BY s.application_end_date ASC, s.id ASC
    ";
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $rule = [
            'min_gpa' => $row['min_gpa'],
            'min_avg_score' => $row['min_avg_score'],
            'max_class_rank_percent' => $row['max_class_rank_percent'],
            'allowed_departments' => $row['allowed_departments'],
            'provider_department' => $row['provider_department'],
            'notes' => $row['notes'],
        ];
        unset(
            $row['min_gpa'],
            $row['min_avg_score'],
            $row['max_class_rank_percent'],
            $row['allowed_departments'],
            $row['provider_department'],
            $row['notes']
        );

        $evaluation = wu_evaluate_scholarship($row, $rule, $context);
        if (!$evaluation['eligible']) {
            continue;
        }

        $row['match_reasons'] = $evaluation['reasons'];
        $row['match_score'] = $evaluation['score'];
        $row['match_summary'] = $evaluation['match_summary'];
        $matches[] = $row;
    }

    usort($matches, function ($a, $b) {
        if ($a['match_score'] === $b['match_score']) {
            return strcmp((string) $a['application_end_date'], (string) $b['application_end_date']);
        }
        return $b['match_score'] <=> $a['match_score'];
    });

    return array_slice($matches, 0, $limit);
}

function wu_upsert_notification(
    mysqli $conn,
    string $username,
    string $type,
    string $title,
    string $message,
    string $dedupKey,
    ?int $applicationId = null,
    ?int $scholarshipId = null
): void {
    if (!wu_table_exists($conn, 'student_notifications')) {
        return;
    }

    $existingId = null;
    $existingEmailSentAt = null;
    if (wu_column_exists($conn, 'student_notifications', 'email_sent_at')) {
        $checkStmt = $conn->prepare('SELECT id, email_sent_at FROM student_notifications WHERE dedup_key = ? LIMIT 1');
        if ($checkStmt) {
            $checkStmt->bind_param('s', $dedupKey);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();
            if ($existing) {
                $existingId = (int) $existing['id'];
                $existingEmailSentAt = $existing['email_sent_at'];
            }
            $checkStmt->close();
        }
    }

    $hasEmailColumns = wu_column_exists($conn, 'student_notifications', 'email_sent_at')
        && wu_column_exists($conn, 'student_notifications', 'email_last_error');

    if ($hasEmailColumns) {
        $sql = "
            INSERT INTO student_notifications
                (student_username, type, title, message, related_application_id, related_scholarship_id, dedup_key, is_read, email_sent_at, email_last_error)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, NULL, NULL)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                message = VALUES(message),
                is_read = IF(student_notifications.is_read = 1, 1, 0)
        ";
    } else {
        $sql = "
            INSERT INTO student_notifications
                (student_username, type, title, message, related_application_id, related_scholarship_id, dedup_key, is_read)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                message = VALUES(message),
                is_read = IF(student_notifications.is_read = 1, 1, 0)
        ";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        'ssssiis',
        $username,
        $type,
        $title,
        $message,
        $applicationId,
        $scholarshipId,
        $dedupKey
    );
    $stmt->execute();
    $insertId = (int) $stmt->insert_id;
    $stmt->close();

    if (!$hasEmailColumns) {
        return;
    }

    $notificationId = $existingId ?: $insertId;
    if ($notificationId <= 0) {
        $idStmt = $conn->prepare('SELECT id, email_sent_at FROM student_notifications WHERE dedup_key = ? LIMIT 1');
        if ($idStmt) {
            $idStmt->bind_param('s', $dedupKey);
            $idStmt->execute();
            $row = $idStmt->get_result()->fetch_assoc();
            if ($row) {
                $notificationId = (int) $row['id'];
                $existingEmailSentAt = $row['email_sent_at'];
            }
            $idStmt->close();
        }
    }

    if ($notificationId > 0) {
        wu_try_send_notification_email(
            $conn,
            $notificationId,
            $username,
            $title,
            $message,
            !empty($existingEmailSentAt)
        );
    }
}

function wu_sync_student_notifications(mysqli $conn, string $username): void
{
    if (!wu_table_exists($conn, 'student_notifications')) {
        return;
    }

    $appSql = "
        SELECT a.id, a.status, a.review_comment, a.application_date, s.id AS scholarship_id, s.name AS scholarship_name
        FROM applications a
        LEFT JOIN scholarships s ON s.id = a.scholarship_id
        WHERE a.student_username = ?
        ORDER BY a.updated_at DESC
    ";
    $appStmt = $conn->prepare($appSql);
    $appStmt->bind_param('s', $username);
    $appStmt->execute();
    $appResult = $appStmt->get_result();

    while ($app = $appResult->fetch_assoc()) {
        $status = (int) $app['status'];
        $appId = (int) $app['id'];
        $scholarshipId = (int) $app['scholarship_id'];
        $scholarshipName = $app['scholarship_name'] ?: '獎學金';

        if ($status === 1) {
            wu_upsert_notification(
                $conn,
                $username,
                'result_approved',
                '審查結果：已通過',
                "恭喜您！您申請的「{$scholarshipName}」已通過審核，預計於下個月撥款。",
                "result-approved-{$appId}",
                $appId,
                $scholarshipId
            );
        } elseif ($status === 0) {
            $comment = trim((string) ($app['review_comment'] ?? ''));
            $detail = $comment !== '' ? $comment : '請至申請紀錄查看審核意見。';
            wu_upsert_notification(
                $conn,
                $username,
                'result_rejected',
                '審查結果：未通過',
                "您的「{$scholarshipName}」申請未通過。原因：{$detail}",
                "result-rejected-{$appId}",
                $appId,
                $scholarshipId
            );
        } elseif ($status === 2) {
            $comment = trim((string) ($app['review_comment'] ?? ''));
            $detail = $comment !== '' ? $comment : '請檢查缺漏資料';
            wu_upsert_notification(
                $conn,
                $username,
                'result_revision',
                '審查結果：需補件',
                "您的「{$scholarshipName}」申請需補件：{$detail}",
                "result-revision-{$appId}",
                $appId,
                $scholarshipId
            );
        }
    }
    $appStmt->close();

    $today = new DateTimeImmutable('today');
    $deadlineLimit = $today->modify('+5 days')->format('Y-m-d');
    $deadlineSql = "
        SELECT s.id, s.name, s.application_end_date
        FROM scholarships s
        WHERE s.is_active = 1
          AND s.application_end_date IS NOT NULL
          AND s.application_end_date >= CURDATE()
          AND s.application_end_date <= ?
          AND s.id NOT IN (
              SELECT scholarship_id FROM applications WHERE student_username = ?
          )
    ";
    $deadlineStmt = $conn->prepare($deadlineSql);
    $deadlineStmt->bind_param('ss', $deadlineLimit, $username);
    $deadlineStmt->execute();
    $deadlineResult = $deadlineStmt->get_result();

    while ($sch = $deadlineResult->fetch_assoc()) {
        $schId = (int) $sch['id'];
        $endDate = $sch['application_end_date'];
        wu_upsert_notification(
            $conn,
            $username,
            'deadline_reminder',
            '截止提醒',
            "「{$sch['name']}」將於 {$endDate} 截止，請把握時間完成申請。",
            "deadline-{$schId}-{$endDate}",
            null,
            $schId
        );
    }
    $deadlineStmt->close();

    $eligibleScholarships = wu_get_eligible_scholarships($conn, $username, 3);
    foreach ($eligibleScholarships as $sch) {
        $schId = (int) $sch['id'];
        $reasonText = implode('；', $sch['match_reasons'] ?? []);
        wu_upsert_notification(
            $conn,
            $username,
            'eligibility_recommendation',
            '為您推薦獎學金',
            "依您的系所與成績，建議申請「{$sch['name']}」。{$reasonText}",
            "recommendation-{$schId}",
            null,
            $schId
        );
    }
}

function wu_fetch_student_notifications(mysqli $conn, string $username, bool $unreadOnly = false): array
{
    if (!wu_table_exists($conn, 'student_notifications')) {
        return [];
    }

    $sql = "
        SELECT id, type, title, message, related_application_id, related_scholarship_id,
               is_read, created_at
        FROM student_notifications
        WHERE student_username = ?
    ";
    if ($unreadOnly) {
        $sql .= ' AND is_read = 0';
    }
    $sql .= ' ORDER BY created_at DESC, id DESC';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_read'] = (int) $row['is_read'];
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

function wu_count_unread_notifications(mysqli $conn, string $username): int
{
    if (!wu_table_exists($conn, 'student_notifications')) {
        return 0;
    }

    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS unread_count FROM student_notifications WHERE student_username = ? AND is_read = 0'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $count = (int) ($stmt->get_result()->fetch_assoc()['unread_count'] ?? 0);
    $stmt->close();

    return $count;
}
