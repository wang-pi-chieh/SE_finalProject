-- Owner: Wu Ru-Ting
-- Scope: student notifications, read status, eligible-scholarship matching rules and results.

CREATE TABLE IF NOT EXISTS `student_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_username` varchar(50) NOT NULL COMMENT '學生帳號',
  `type` varchar(50) NOT NULL COMMENT 'result_approved/result_rejected/result_revision/deadline_reminder/eligibility_recommendation',
  `title` varchar(255) NOT NULL COMMENT '通知標題',
  `message` text NOT NULL COMMENT '通知內容',
  `related_application_id` int(11) DEFAULT NULL COMMENT '關聯申請編號',
  `related_scholarship_id` int(11) DEFAULT NULL COMMENT '關聯獎學金編號',
  `dedup_key` varchar(255) NOT NULL COMMENT '去重用鍵值',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已讀',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_notification_dedup` (`dedup_key`),
  KEY `idx_student_notifications_user_read` (`student_username`, `is_read`, `created_at`),
  KEY `idx_student_notifications_application` (`related_application_id`),
  KEY `idx_student_notifications_scholarship` (`related_scholarship_id`),
  CONSTRAINT `student_notifications_student_fk` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE,
  CONSTRAINT `student_notifications_application_fk` FOREIGN KEY (`related_application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_notifications_scholarship_fk` FOREIGN KEY (`related_scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生站內通知';

CREATE TABLE IF NOT EXISTS `scholarship_eligibility_rules` (
  `scholarship_id` int(11) NOT NULL COMMENT '獎學金編號',
  `min_gpa` decimal(4,2) DEFAULT NULL COMMENT '最低 GPA',
  `min_avg_score` decimal(5,2) DEFAULT NULL COMMENT '最低平均成績',
  `max_class_rank_percent` decimal(5,2) DEFAULT NULL COMMENT '班排百分比上限，例如 10 代表前 10%',
  `allowed_departments` text DEFAULT NULL COMMENT '允許系所 JSON 陣列，NULL 代表不限',
  `provider_department` varchar(100) DEFAULT NULL COMMENT '獎助單位對應系所',
  `notes` varchar(255) DEFAULT NULL COMMENT '規則說明',
  PRIMARY KEY (`scholarship_id`),
  CONSTRAINT `scholarship_eligibility_rules_fk` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='獎學金資格比對規則';

INSERT INTO `scholarship_eligibility_rules`
  (`scholarship_id`, `min_gpa`, `min_avg_score`, `max_class_rank_percent`, `allowed_departments`, `provider_department`, `notes`)
VALUES
  (1, 3.50, 85.00, NULL, NULL, NULL, '提供予家境清寒且學業成績優異之學生'),
  (2, NULL, NULL, 10.00, '["資訊工程學系"]', '資訊工程學系', '限各系學生申請，學業成績需達班排前 10%'),
  (3, 3.80, 90.00, NULL, NULL, NULL, '獎勵發表頂尖期刊論文之學生'),
  (4, 3.00, 80.00, NULL, NULL, NULL, '補助赴海外交換學生之機票與生活費'),
  (5, NULL, 60.00, NULL, NULL, NULL, '弱勢學生生活津貼，前一學期成績須達 60 分以上')
ON DUPLICATE KEY UPDATE
  `min_gpa` = VALUES(`min_gpa`),
  `min_avg_score` = VALUES(`min_avg_score`),
  `max_class_rank_percent` = VALUES(`max_class_rank_percent`),
  `allowed_departments` = VALUES(`allowed_departments`),
  `provider_department` = VALUES(`provider_department`),
  `notes` = VALUES(`notes`);
