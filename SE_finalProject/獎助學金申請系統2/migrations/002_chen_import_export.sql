-- Owner: Chen Yi-Zhong
-- Scope: sponsor-data import batches, import confirmation, announcement notifications, report export metadata.

CREATE TABLE IF NOT EXISTS `scholarship_import_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_filename` varchar(255) NOT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `status` enum('uploaded','confirmed','failed') NOT NULL DEFAULT 'uploaded',
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `valid_rows` int(11) NOT NULL DEFAULT 0,
  `error_rows` int(11) NOT NULL DEFAULT 0,
  `import_data` longtext NOT NULL COMMENT 'JSON preview rows parsed from CSV before confirmation',
  `error_report` longtext DEFAULT NULL COMMENT 'JSON validation or import errors',
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scholarship_import_batches_status_created` (`status`, `created_at`),
  KEY `idx_scholarship_import_batches_uploader` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `scholarship_export_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `export_type` varchar(50) NOT NULL DEFAULT 'scholarships_csv',
  `exported_by` varchar(50) DEFAULT NULL,
  `row_count` int(11) NOT NULL DEFAULT 0,
  `file_name` varchar(255) DEFAULT NULL,
  `filters` longtext DEFAULT NULL COMMENT 'JSON export filters or context',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scholarship_export_logs_created` (`created_at`),
  KEY `idx_scholarship_export_logs_exporter` (`exported_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
