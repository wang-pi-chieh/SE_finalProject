-- Owner: Tsai Bo-Yu
-- Scope: review scoring, result integration, final award lists, disbursement records.
-- Review result history is stored in review_records, applications.review_score,
-- and final_award_results. award_disbursements stores payout workflow state.

SET @add_application_review_score = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'applications'
     AND column_name = 'review_score') = 0,
  'ALTER TABLE `applications` ADD COLUMN `review_score` decimal(5,2) DEFAULT NULL AFTER `review_comment`',
  'SELECT 1'
);
PREPARE stmt_add_application_review_score FROM @add_application_review_score;
EXECUTE stmt_add_application_review_score;
DEALLOCATE PREPARE stmt_add_application_review_score;

SET @add_review_score = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'review_records'
     AND column_name = 'score') = 0,
  'ALTER TABLE `review_records` ADD COLUMN `score` decimal(5,2) DEFAULT NULL AFTER `note`',
  'SELECT 1'
);
PREPARE stmt_add_review_score FROM @add_review_score;
EXECUTE stmt_add_review_score;
DEALLOCATE PREPARE stmt_add_review_score;

SET @add_review_stage = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'review_records'
     AND column_name = 'stage') = 0,
  'ALTER TABLE `review_records` ADD COLUMN `stage` varchar(50) NOT NULL DEFAULT ''initial'' AFTER `score`',
  'SELECT 1'
);
PREPARE stmt_add_review_stage FROM @add_review_stage;
EXECUTE stmt_add_review_stage;
DEALLOCATE PREPARE stmt_add_review_stage;

SET @add_review_created_at = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'review_records'
     AND column_name = 'created_at') = 0,
  'ALTER TABLE `review_records` ADD COLUMN `created_at` datetime NOT NULL DEFAULT current_timestamp() AFTER `admin_username`',
  'SELECT 1'
);
PREPARE stmt_add_review_created_at FROM @add_review_created_at;
EXECUTE stmt_add_review_created_at;
DEALLOCATE PREPARE stmt_add_review_created_at;

CREATE TABLE IF NOT EXISTS `final_award_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scholarship_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `student_username` varchar(50) NOT NULL,
  `final_score` decimal(6,2) NOT NULL DEFAULT 0,
  `rank_no` int(11) NOT NULL,
  `result` enum('selected','waitlisted','not_selected') NOT NULL DEFAULT 'waitlisted',
  `generated_by` varchar(50) DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_final_award_application` (`application_id`),
  KEY `idx_final_award_scholarship_rank` (`scholarship_id`, `rank_no`),
  KEY `idx_final_award_result` (`result`, `generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `award_disbursements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `handled_by` varchar(50) DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_award_disbursements_application` (`application_id`),
  KEY `idx_award_disbursements_status_updated` (`status`, `updated_at`),
  KEY `idx_award_disbursements_handler` (`handled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `award_disbursements` (`application_id`)
SELECT `id`
FROM `applications`
WHERE `status` = 1;
