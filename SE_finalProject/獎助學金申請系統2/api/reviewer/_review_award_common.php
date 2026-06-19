<?php

function reviewer_award_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function reviewer_award_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS count
         FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?"
    );
    if (!$stmt) return false;
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ((int) ($row['count'] ?? 0)) > 0;
}

function reviewer_award_column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS count
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ((int) ($row['count'] ?? 0)) > 0;
}

function reviewer_award_index_exists(mysqli $conn, string $table, string $index): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS count
         FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ss', $table, $index);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ((int) ($row['count'] ?? 0)) > 0;
}

function reviewer_award_ensure_schema(mysqli $conn): void
{
    if (!reviewer_award_column_exists($conn, 'applications', 'review_score')) {
        if (!$conn->query("ALTER TABLE applications ADD COLUMN review_score decimal(5,2) DEFAULT NULL AFTER review_comment")) {
            throw new Exception('補上 applications.review_score 失敗：' . $conn->error);
        }
    }

    $reviewColumns = [
        'score' => "ALTER TABLE review_records ADD COLUMN score decimal(5,2) DEFAULT NULL AFTER note",
        'stage' => "ALTER TABLE review_records ADD COLUMN stage varchar(50) NOT NULL DEFAULT 'initial' AFTER score",
        'created_at' => "ALTER TABLE review_records ADD COLUMN created_at datetime NOT NULL DEFAULT current_timestamp() AFTER admin_username",
    ];
    foreach ($reviewColumns as $column => $sql) {
        if (!reviewer_award_column_exists($conn, 'review_records', $column)) {
            if (!$conn->query($sql)) {
                throw new Exception("補上 review_records.{$column} 失敗：" . $conn->error);
            }
        }
    }

    if (!reviewer_award_index_exists($conn, 'review_records', 'idx_review_records_app_stage')) {
        $conn->query("ALTER TABLE review_records ADD KEY idx_review_records_app_stage (application_id, stage, review_date)");
    }
    if (!reviewer_award_index_exists($conn, 'review_records', 'idx_review_records_score')) {
        $conn->query("ALTER TABLE review_records ADD KEY idx_review_records_score (application_id, score)");
    }

    if (!reviewer_award_table_exists($conn, 'final_award_results')) {
        $sql = "
            CREATE TABLE final_award_results (
              id int(11) NOT NULL AUTO_INCREMENT,
              scholarship_id int(11) NOT NULL,
              application_id int(11) NOT NULL,
              student_username varchar(50) NOT NULL,
              final_score decimal(6,2) NOT NULL DEFAULT 0,
              rank_no int(11) NOT NULL,
              result enum('selected','waitlisted','not_selected') NOT NULL DEFAULT 'waitlisted',
              generated_by varchar(50) DEFAULT NULL,
              generated_at datetime NOT NULL DEFAULT current_timestamp(),
              confirmed_at datetime DEFAULT NULL,
              confirmed_by varchar(50) DEFAULT NULL,
              note varchar(255) DEFAULT NULL,
              PRIMARY KEY (id),
              UNIQUE KEY uniq_final_award_application (application_id),
              KEY idx_final_award_scholarship_rank (scholarship_id, rank_no),
              KEY idx_final_award_result (result, generated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        if (!$conn->query($sql)) {
            throw new Exception('建立 final_award_results 失敗：' . $conn->error);
        }
    }
}

function reviewer_award_actor(mysqli $conn, string $username): ?array
{
    $stmt = $conn->prepare("SELECT username, role, real_name FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function reviewer_award_is_admin_role(string $role): bool
{
    return in_array($role, ['系統管理員', 'system_admin', 'admin'], true);
}

function reviewer_award_is_unit_role(string $role): bool
{
    return in_array($role, ['獎助單位', 'scholarship_unit', 'reviewer'], true);
}

function reviewer_award_scope(array $actor, string $scholarshipAlias = 's'): array
{
    $role = (string) ($actor['role'] ?? '');
    if (reviewer_award_is_admin_role($role)) {
        return ['sql' => '', 'types' => '', 'params' => []];
    }

    if (reviewer_award_is_unit_role($role)) {
        return [
            'sql' => " AND {$scholarshipAlias}.provider_username = ?",
            'types' => 's',
            'params' => [(string) $actor['username']],
        ];
    }

    throw new Exception('沒有審查或錄取名單管理權限');
}

function reviewer_award_fetch_application(mysqli $conn, int $applicationId, array $actor): ?array
{
    $scope = reviewer_award_scope($actor, 's');
    $sql = "
        SELECT a.id, a.scholarship_id, a.student_username, a.status, a.review_comment,
               a.review_score, s.provider_username, s.name AS scholarship_name
        FROM applications a
        INNER JOIN scholarships s ON s.id = a.scholarship_id
        WHERE a.id = ? {$scope['sql']}
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('準備讀取申請資料失敗：' . $conn->error);
    }
    $types = 'i' . $scope['types'];
    $params = array_merge([$applicationId], $scope['params']);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function reviewer_award_stage_label(string $stage): string
{
    $labels = [
        'initial' => '初審',
        'second' => '複審',
        'final' => '決審',
        'supplement' => '補件審查',
        'draft' => '草稿',
    ];
    return $labels[$stage] ?? $stage;
}

function reviewer_award_normalize_status($status): int
{
    if ($status === '1' || $status === 1 || $status === 'approved' || $status === '通過') return 1;
    if ($status === '2' || $status === 2 || $status === 'supplement' || $status === 'needs_action' || $status === '需補件') return 2;
    if ($status === '0' || $status === 0 || $status === 'rejected' || $status === '駁回') return 0;
    if ($status === '3' || $status === 3 || $status === 'reviewing' || $status === '未審查' || $status === 'pending') return 3;
    if (is_numeric($status)) return (int) $status;
    return 3;
}

function reviewer_award_fetch_final_list(mysqli $conn, array $actor, int $scholarshipId = 0): array
{
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
            a.application_date,
            a.review_score,
            s.id AS scholarship_id,
            s.name AS scholarship_name,
            s.quota,
            s.amount,
            COALESCE(u.real_name, a.student_username) AS student_name,
            st.department,
            g.avg_score AS latest_avg_score,
            g.gpa AS latest_gpa,
            COALESCE(rr.integrated_score, a.review_score, 0) AS final_score,
            COALESCE(rr.review_stage_count, 0) AS review_stage_count,
            COALESCE(rr.review_stages, '') AS review_stages,
            far.result AS confirmed_result,
            far.confirmed_at,
            far.confirmed_by
        FROM applications a
        INNER JOIN scholarships s ON s.id = a.scholarship_id
        LEFT JOIN users u ON u.username = a.student_username
        LEFT JOIN students st ON st.username = a.student_username
        LEFT JOIN grades g ON g.student_username = a.student_username
        LEFT JOIN grades g2 ON g2.student_username = g.student_username
          AND (
            CAST(g2.academic_year AS UNSIGNED) > CAST(g.academic_year AS UNSIGNED)
            OR (
              CAST(g2.academic_year AS UNSIGNED) = CAST(g.academic_year AS UNSIGNED)
              AND CASE WHEN g2.semester IN ('下', '2') THEN 2 ELSE 1 END > CASE WHEN g.semester IN ('下', '2') THEN 2 ELSE 1 END
            )
          )
        LEFT JOIN (
            SELECT application_id,
                   ROUND(AVG(score), 2) AS integrated_score,
                   COUNT(*) AS review_stage_count,
                   GROUP_CONCAT(DISTINCT stage ORDER BY stage SEPARATOR ',') AS review_stages
            FROM review_records
            WHERE score IS NOT NULL
            GROUP BY application_id
        ) rr ON rr.application_id = a.id
        LEFT JOIN final_award_results far ON far.application_id = a.id
        WHERE a.status = 1
          AND g2.student_username IS NULL
          {$scope['sql']}
          {$scholarshipFilter}
        ORDER BY s.id ASC, final_score DESC, g.avg_score DESC, a.application_date ASC, a.id ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('準備最終名單查詢失敗：' . $conn->error);
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rankByScholarship = [];
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $sid = (int) $row['scholarship_id'];
        $rankByScholarship[$sid] = ($rankByScholarship[$sid] ?? 0) + 1;
        $rank = $rankByScholarship[$sid];
        $quota = max(0, (int) ($row['quota'] ?? 0));
        $calculated = ($quota === 0 || $rank <= $quota) ? 'selected' : 'waitlisted';
        $stages = array_values(array_filter(array_map('trim', explode(',', (string) $row['review_stages']))));
        $stageLabels = array_map('reviewer_award_stage_label', $stages);

        $rows[] = [
            'application_id' => (int) $row['application_id'],
            'scholarship_id' => $sid,
            'scholarship_name' => $row['scholarship_name'],
            'quota' => $quota,
            'rank_no' => $rank,
            'result' => $calculated,
            'confirmed_result' => $row['confirmed_result'],
            'confirmed_at' => $row['confirmed_at'],
            'confirmed_by' => $row['confirmed_by'],
            'student_username' => $row['student_username'],
            'student_name' => $row['student_name'],
            'department' => $row['department'],
            'amount' => $row['amount'],
            'application_date' => $row['application_date'],
            'review_score' => $row['review_score'] !== null ? (float) $row['review_score'] : null,
            'final_score' => round((float) $row['final_score'], 2),
            'score_missing' => $row['review_score'] === null && (int) $row['review_stage_count'] === 0,
            'latest_avg_score' => $row['latest_avg_score'] !== null ? (float) $row['latest_avg_score'] : null,
            'latest_gpa' => $row['latest_gpa'] !== null ? (float) $row['latest_gpa'] : null,
            'review_stage_count' => (int) $row['review_stage_count'],
            'review_stages' => $stages,
            'review_stage_labels' => $stageLabels,
        ];
    }

    return $rows;
}

function reviewer_award_save_final_list(mysqli $conn, array $rows, array $actor): int
{
    $saved = 0;
    $stmt = $conn->prepare(
        "INSERT INTO final_award_results
            (scholarship_id, application_id, student_username, final_score, rank_no, result, generated_by, generated_at, confirmed_at, confirmed_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
         ON DUPLICATE KEY UPDATE
            scholarship_id = VALUES(scholarship_id),
            student_username = VALUES(student_username),
            final_score = VALUES(final_score),
            rank_no = VALUES(rank_no),
            result = VALUES(result),
            generated_by = VALUES(generated_by),
            generated_at = NOW(),
            confirmed_at = NOW(),
            confirmed_by = VALUES(confirmed_by)"
    );
    if (!$stmt) {
        throw new Exception('準備寫入最終名單失敗：' . $conn->error);
    }

    $actorUsername = (string) $actor['username'];
    foreach ($rows as $row) {
        $scholarshipId = (int) $row['scholarship_id'];
        $applicationId = (int) $row['application_id'];
        $studentUsername = (string) $row['student_username'];
        $finalScore = (float) $row['final_score'];
        $rankNo = (int) $row['rank_no'];
        $result = (string) $row['result'];
        $stmt->bind_param(
            'iisdisss',
            $scholarshipId,
            $applicationId,
            $studentUsername,
            $finalScore,
            $rankNo,
            $result,
            $actorUsername,
            $actorUsername
        );
        if (!$stmt->execute()) {
            throw new Exception('寫入最終名單失敗：' . $stmt->error);
        }
        $saved++;
    }

    return $saved;
}

?>
