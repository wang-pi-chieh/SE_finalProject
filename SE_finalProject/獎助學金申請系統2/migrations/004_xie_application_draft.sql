-- Owner: Hsieh Tsung-Feng
-- Scope: server-side student application drafts for 30-second autosave.
CREATE TABLE IF NOT EXISTS `application_drafts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_username` varchar(50) NOT NULL,
  `draft_key` varchar(120) NOT NULL,
  `scholarship_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `draft_data` longtext NOT NULL COMMENT 'JSON form field snapshot saved by autosave.js',
  `file_metadata` longtext DEFAULT NULL COMMENT 'JSON file-name snapshot; files are not uploaded until submit',
  `last_error` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_application_draft_user_key` (`student_username`, `draft_key`),
  KEY `idx_application_drafts_student_updated` (`student_username`, `updated_at`),
  KEY `idx_application_drafts_application` (`application_id`),
  KEY `idx_application_drafts_scholarship` (`scholarship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
