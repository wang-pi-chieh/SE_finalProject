<?php
function admin_ops_json($payload, $status_code = 200)
{
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function admin_ops_table_exists($conn, $table_name)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $table_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row && (int) $row['count'] > 0;
}

function admin_ops_require_table($conn, $table_name)
{
    if (!admin_ops_table_exists($conn, $table_name)) {
        admin_ops_json([
            'success' => false,
            'message' => "缺少資料表 {$table_name}，請先匯入 migrations/001_wang_issue_backup.sql"
        ], 500);
    }
}

function admin_ops_column_exists($conn, $table_name, $column_name)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $table_name, $column_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row && (int) $row['count'] > 0;
}

function admin_ops_index_exists($conn, $table_name, $index_name)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $table_name, $index_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row && (int) $row['count'] > 0;
}

function admin_ops_ensure_issue_schema($conn)
{
    admin_ops_require_table($conn, 'issue_reports');

    $columns = [
        'reporter_role' => "ALTER TABLE issue_reports ADD COLUMN reporter_role varchar(50) DEFAULT NULL AFTER reporter_username",
        'contact_email' => "ALTER TABLE issue_reports ADD COLUMN contact_email varchar(100) DEFAULT NULL AFTER description",
        'contact_phone' => "ALTER TABLE issue_reports ADD COLUMN contact_phone varchar(30) DEFAULT NULL AFTER contact_email"
    ];

    foreach ($columns as $column => $sql) {
        if (!admin_ops_column_exists($conn, 'issue_reports', $column)) {
            $conn->query($sql);
        }
    }

    if (!admin_ops_table_exists($conn, 'issue_report_notifications')) {
        $conn->query("
            CREATE TABLE issue_report_notifications (
              id int(11) NOT NULL AUTO_INCREMENT,
              issue_report_id int(11) NOT NULL,
              recipient_username varchar(50) NOT NULL,
              title varchar(255) NOT NULL,
              message text NOT NULL,
              is_read tinyint(1) NOT NULL DEFAULT 0,
              created_at datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (id),
              KEY idx_issue_notifications_user_read (recipient_username, is_read, created_at),
              KEY idx_issue_notifications_report (issue_report_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

function admin_ops_ensure_restore_schema($conn)
{
    if (!admin_ops_table_exists($conn, 'restore_logs')) {
        $conn->query("
            CREATE TABLE restore_logs (
              id int(11) NOT NULL AUTO_INCREMENT,
              backup_job_id int(11) DEFAULT NULL,
              restored_by varchar(50) DEFAULT NULL,
              status enum('started','completed','failed') NOT NULL DEFAULT 'started',
              message text DEFAULT NULL,
              created_at datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (id),
              KEY idx_restore_logs_job (backup_job_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!admin_ops_table_exists($conn, 'restore_uploads')) {
        $conn->query("
            CREATE TABLE restore_uploads (
              id int(11) NOT NULL AUTO_INCREMENT,
              restore_log_id int(11) DEFAULT NULL,
              original_name varchar(255) NOT NULL,
              stored_name varchar(255) NOT NULL,
              stored_path varchar(255) NOT NULL,
              file_size int(11) NOT NULL DEFAULT 0,
              uploaded_by varchar(50) DEFAULT NULL,
              created_at datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (id),
              KEY idx_restore_uploads_created (created_at),
              KEY idx_restore_uploads_log (restore_log_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

function admin_ops_ensure_archive_schema($conn)
{
    if (!admin_ops_table_exists($conn, 'data_archives')) {
        $conn->query("
            CREATE TABLE data_archives (
              id int(11) NOT NULL AUTO_INCREMENT,
              archive_name varchar(120) NOT NULL,
              source_table varchar(80) NOT NULL,
              record_count int(11) NOT NULL DEFAULT 0,
              archive_path varchar(255) DEFAULT NULL,
              file_size int(11) NOT NULL DEFAULT 0,
              created_by varchar(50) DEFAULT NULL,
              created_at datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (id),
              KEY idx_data_archives_source_created (source_table, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $columns = [
        'archive_path' => "ALTER TABLE data_archives ADD COLUMN archive_path varchar(255) DEFAULT NULL AFTER record_count",
        'file_size' => "ALTER TABLE data_archives ADD COLUMN file_size int(11) NOT NULL DEFAULT 0 AFTER archive_path",
        'downloaded_at' => "ALTER TABLE data_archives ADD COLUMN downloaded_at datetime DEFAULT NULL AFTER file_size",
        'downloaded_by' => "ALTER TABLE data_archives ADD COLUMN downloaded_by varchar(50) DEFAULT NULL AFTER downloaded_at",
        'original_deleted_at' => "ALTER TABLE data_archives ADD COLUMN original_deleted_at datetime DEFAULT NULL AFTER file_size",
        'original_deleted_by' => "ALTER TABLE data_archives ADD COLUMN original_deleted_by varchar(50) DEFAULT NULL AFTER original_deleted_at",
        'original_deleted_count' => "ALTER TABLE data_archives ADD COLUMN original_deleted_count int(11) NOT NULL DEFAULT 0 AFTER original_deleted_by"
    ];

    foreach ($columns as $column => $sql) {
        if (!admin_ops_column_exists($conn, 'data_archives', $column)) {
            $conn->query($sql);
        }
    }
}

function admin_ops_ensure_teacher_notification_schema($conn)
{
    if (!admin_ops_table_exists($conn, 'teacher_notifications')) {
        $conn->query("
            CREATE TABLE teacher_notifications (
              id int(11) NOT NULL AUTO_INCREMENT,
              teacher_username varchar(50) NOT NULL,
              type varchar(50) NOT NULL,
              title varchar(255) NOT NULL,
              message text NOT NULL,
              related_application_id int(11) DEFAULT NULL,
              related_issue_report_id int(11) DEFAULT NULL,
              dedup_key varchar(255) NOT NULL,
              is_read tinyint(1) NOT NULL DEFAULT 0,
              created_at datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (id),
              UNIQUE KEY uniq_teacher_notification_dedup (dedup_key),
              KEY idx_teacher_notifications_user_read (teacher_username, is_read, created_at),
              KEY idx_teacher_notifications_application (related_application_id),
              KEY idx_teacher_notifications_issue_report (related_issue_report_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $columns = [
        'teacher_username' => "ALTER TABLE teacher_notifications ADD COLUMN teacher_username varchar(50) NOT NULL AFTER id",
        'type' => "ALTER TABLE teacher_notifications ADD COLUMN type varchar(50) NOT NULL DEFAULT 'system' AFTER teacher_username",
        'title' => "ALTER TABLE teacher_notifications ADD COLUMN title varchar(255) NOT NULL DEFAULT '' AFTER type",
        'message' => "ALTER TABLE teacher_notifications ADD COLUMN message text NOT NULL AFTER title",
        'related_application_id' => "ALTER TABLE teacher_notifications ADD COLUMN related_application_id int(11) DEFAULT NULL AFTER message",
        'related_issue_report_id' => "ALTER TABLE teacher_notifications ADD COLUMN related_issue_report_id int(11) DEFAULT NULL AFTER related_application_id",
        'dedup_key' => "ALTER TABLE teacher_notifications ADD COLUMN dedup_key varchar(255) NOT NULL DEFAULT '' AFTER related_issue_report_id",
        'is_read' => "ALTER TABLE teacher_notifications ADD COLUMN is_read tinyint(1) NOT NULL DEFAULT 0 AFTER dedup_key",
        'created_at' => "ALTER TABLE teacher_notifications ADD COLUMN created_at datetime NOT NULL DEFAULT current_timestamp() AFTER is_read"
    ];

    foreach ($columns as $column => $sql) {
        if (!admin_ops_column_exists($conn, 'teacher_notifications', $column)) {
            $conn->query($sql);
        }
    }

    if (!admin_ops_index_exists($conn, 'teacher_notifications', 'uniq_teacher_notification_dedup')) {
        $conn->query("ALTER TABLE teacher_notifications ADD UNIQUE KEY uniq_teacher_notification_dedup (dedup_key)");
    }

    if (!admin_ops_index_exists($conn, 'teacher_notifications', 'idx_teacher_notifications_user_read')) {
        $conn->query("ALTER TABLE teacher_notifications ADD KEY idx_teacher_notifications_user_read (teacher_username, is_read, created_at)");
    }
}

function admin_ops_actor()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['username']) ? $_SESSION['username'] : 'System Admin';
}
?>
