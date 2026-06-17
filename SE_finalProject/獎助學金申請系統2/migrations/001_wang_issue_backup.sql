CREATE TABLE IF NOT EXISTS `issue_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_username` varchar(50) DEFAULT NULL,
  `reporter_role` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `status` enum('open','processing','resolved') NOT NULL DEFAULT 'open',
  `handled_by` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_issue_reports_status_created` (`status`, `created_at`),
  KEY `idx_issue_reports_reporter` (`reporter_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `issue_report_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_report_id` int(11) NOT NULL,
  `recipient_username` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent_at` datetime DEFAULT NULL,
  `email_last_error` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_issue_notifications_user_read` (`recipient_username`, `is_read`, `created_at`),
  KEY `idx_issue_notifications_report` (`issue_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `teacher_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_username` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_application_id` int(11) DEFAULT NULL,
  `related_issue_report_id` int(11) DEFAULT NULL,
  `dedup_key` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent_at` datetime DEFAULT NULL,
  `email_last_error` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_teacher_notification_dedup` (`dedup_key`),
  KEY `idx_teacher_notifications_user_read` (`teacher_username`, `is_read`, `created_at`),
  KEY `idx_teacher_notifications_application` (`related_application_id`),
  KEY `idx_teacher_notifications_issue_report` (`related_issue_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `backup_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_name` varchar(120) NOT NULL,
  `status` enum('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
  `requested_by` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_backup_jobs_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restore_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_job_id` int(11) DEFAULT NULL,
  `restored_by` varchar(50) DEFAULT NULL,
  `status` enum('started','completed','failed') NOT NULL DEFAULT 'started',
  `message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_restore_logs_job` (`backup_job_id`),
  CONSTRAINT `restore_logs_backup_job_fk`
    FOREIGN KEY (`backup_job_id`) REFERENCES `backup_jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restore_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restore_log_id` int(11) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `stored_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_restore_uploads_created` (`created_at`),
  KEY `idx_restore_uploads_log` (`restore_log_id`),
  CONSTRAINT `restore_uploads_log_fk`
    FOREIGN KEY (`restore_log_id`) REFERENCES `restore_logs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `archive_name` varchar(120) NOT NULL,
  `source_table` varchar(80) NOT NULL,
  `record_count` int(11) NOT NULL DEFAULT 0,
  `archive_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `downloaded_at` datetime DEFAULT NULL,
  `downloaded_by` varchar(50) DEFAULT NULL,
  `original_deleted_at` datetime DEFAULT NULL,
  `original_deleted_by` varchar(50) DEFAULT NULL,
  `original_deleted_count` int(11) NOT NULL DEFAULT 0,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_data_archives_source_created` (`source_table`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @add_issue_email_sent_at = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'issue_report_notifications'
     AND column_name = 'email_sent_at') = 0,
  'ALTER TABLE `issue_report_notifications` ADD COLUMN `email_sent_at` datetime DEFAULT NULL AFTER `is_read`',
  'SELECT 1'
);
PREPARE stmt_add_issue_email_sent_at FROM @add_issue_email_sent_at;
EXECUTE stmt_add_issue_email_sent_at;
DEALLOCATE PREPARE stmt_add_issue_email_sent_at;

SET @add_issue_email_last_error = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'issue_report_notifications'
     AND column_name = 'email_last_error') = 0,
  'ALTER TABLE `issue_report_notifications` ADD COLUMN `email_last_error` varchar(255) DEFAULT NULL AFTER `email_sent_at`',
  'SELECT 1'
);
PREPARE stmt_add_issue_email_last_error FROM @add_issue_email_last_error;
EXECUTE stmt_add_issue_email_last_error;
DEALLOCATE PREPARE stmt_add_issue_email_last_error;

SET @add_teacher_email_sent_at = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'teacher_notifications'
     AND column_name = 'email_sent_at') = 0,
  'ALTER TABLE `teacher_notifications` ADD COLUMN `email_sent_at` datetime DEFAULT NULL AFTER `is_read`',
  'SELECT 1'
);
PREPARE stmt_add_teacher_email_sent_at FROM @add_teacher_email_sent_at;
EXECUTE stmt_add_teacher_email_sent_at;
DEALLOCATE PREPARE stmt_add_teacher_email_sent_at;

SET @add_teacher_email_last_error = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'teacher_notifications'
     AND column_name = 'email_last_error') = 0,
  'ALTER TABLE `teacher_notifications` ADD COLUMN `email_last_error` varchar(255) DEFAULT NULL AFTER `email_sent_at`',
  'SELECT 1'
);
PREPARE stmt_add_teacher_email_last_error FROM @add_teacher_email_last_error;
EXECUTE stmt_add_teacher_email_last_error;
DEALLOCATE PREPARE stmt_add_teacher_email_last_error;

INSERT INTO `issue_reports` (`reporter_username`, `title`, `description`, `status`)
SELECT 'System Tester', '申請流程測試問題', '測試用問題回報，可用於驗證 open/processing/resolved 狀態切換。', 'open'
WHERE NOT EXISTS (SELECT 1 FROM `issue_reports` LIMIT 1);
