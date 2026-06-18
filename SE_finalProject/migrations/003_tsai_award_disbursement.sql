-- Owner: Tsai Bo-Yu
-- Scope: review scoring, result integration, final award lists, disbursement records.
-- Review result history is stored in review_records and applications.status.
-- This table stores payout/disbursement workflow state for approved applications.

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
