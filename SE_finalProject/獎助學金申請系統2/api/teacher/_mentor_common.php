<?php

function mentor_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function mentor_ensure_assignments_table(mysqli $conn): void
{
    $ok = $conn->query("
        CREATE TABLE IF NOT EXISTS mentor_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_username VARCHAR(50) NOT NULL,
            department VARCHAR(100) NOT NULL,
            parity_rule ENUM('odd', 'even', 'all') NOT NULL DEFAULT 'all',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_mentor_assignment (teacher_username, department),
            INDEX idx_mentor_department_rule (department, parity_rule)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    if (!$ok) {
        mentor_json_response(['success' => false, 'message' => '導師分配資料表初始化失敗'], 500);
    }
}

function mentor_ensure_scholarship_rules_table(mysqli $conn): void
{
    $ok = $conn->query("
        CREATE TABLE IF NOT EXISTS mentor_scholarship_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            scholarship_id INT NOT NULL UNIQUE,
            department_filter TEXT NULL,
            min_avg_score DECIMAL(5,2) NULL,
            max_rank_percent DECIMAL(5,4) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    if (!$ok) {
        mentor_json_response(['success' => false, 'message' => '導師獎學金規則資料表初始化失敗'], 500);
    }
}

function mentor_get_assignment(mysqli $conn, string $teacher): array
{
    mentor_ensure_assignments_table($conn);

    $assignmentStmt = $conn->prepare("
        SELECT department, parity_rule
        FROM mentor_assignments
        WHERE teacher_username = ?
        ORDER BY id ASC
        LIMIT 1
    ");
    if (!$assignmentStmt) {
        mentor_json_response(['success' => false, 'message' => '導師分配查詢初始化失敗'], 500);
    }

    $assignmentStmt->bind_param('s', $teacher);
    $assignmentStmt->execute();
    $assignment = $assignmentStmt->get_result()->fetch_assoc();
    $assignmentStmt->close();

    if ($assignment) {
        return $assignment;
    }

    $teacherStmt = $conn->prepare("SELECT department FROM teachers WHERE username = ? LIMIT 1");
    if (!$teacherStmt) {
        mentor_json_response(['success' => false, 'message' => '導師資料查詢初始化失敗'], 500);
    }

    $teacherStmt->bind_param('s', $teacher);
    $teacherStmt->execute();
    $teacherRow = $teacherStmt->get_result()->fetch_assoc();
    $teacherStmt->close();

    return ['department' => $teacherRow['department'] ?? '', 'parity_rule' => 'all'];
}

function mentor_parity_sql(string $usernameColumn, ?string $parityRule): string
{
    if ($parityRule === 'odd') {
        return " AND CAST(RIGHT({$usernameColumn}, 1) AS UNSIGNED) % 2 = 1";
    }

    if ($parityRule === 'even') {
        return " AND CAST(RIGHT({$usernameColumn}, 1) AS UNSIGNED) % 2 = 0";
    }

    return '';
}
