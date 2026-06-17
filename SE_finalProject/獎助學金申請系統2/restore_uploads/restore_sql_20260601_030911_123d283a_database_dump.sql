-- Scholarship System Database Dump
-- Date: 2026-05-31 18:11:25

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for `applications`
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請編號',
  `student_username` varchar(50) NOT NULL COMMENT '申請學生帳號',
  `scholarship_id` int(11) NOT NULL COMMENT '申請獎學金編號',
  `application_date` date NOT NULL DEFAULT curdate() COMMENT '申請日期',
  `academic_year` varchar(10) DEFAULT NULL COMMENT '學年 (e.g., 112)',
  `semester` varchar(10) DEFAULT NULL COMMENT '學期 (e.g., 1=上, 2=下)',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `recommendation_required` tinyint(1) DEFAULT 0 COMMENT '是否需要推薦信',
  `biography` text DEFAULT NULL COMMENT '自傳',
  `family_housing_status` varchar(50) DEFAULT NULL COMMENT '家庭居住狀況',
  `personal_housing_status` varchar(50) DEFAULT NULL COMMENT '個人居住狀況',
  `has_student_loan` varchar(10) DEFAULT NULL COMMENT '是否就學貸款 (是/否)',
  `tuition_waiver` varchar(50) DEFAULT NULL COMMENT '學雜費減免身分',
  `previous_scholarship_name` varchar(255) DEFAULT NULL COMMENT '上學期獲獎助學金名稱',
  `proof_documents` text DEFAULT NULL COMMENT '證明文件路徑 (JSON 或逗號分隔)',
  `application_documents` text DEFAULT NULL COMMENT '檢附申請文件路徑',
  `family_situation_desc` text DEFAULT NULL COMMENT '家庭狀況說明',
  `family_members_desc` text DEFAULT NULL COMMENT '家庭成員狀況',
  `referrer_name` varchar(50) DEFAULT NULL COMMENT '推薦人姓名 (若有)',
  `referrer_username` varchar(50) DEFAULT NULL,
  `referrer_relationship` varchar(50) DEFAULT NULL COMMENT '與推薦人關係',
  `review_comment` text DEFAULT NULL COMMENT '審查意見',
  `reviewed_at` timestamp NULL DEFAULT NULL COMMENT '審查時間',
  `reviewed_by` varchar(50) DEFAULT NULL COMMENT '審查人員',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 3 COMMENT '0=Rejected, 1=Approved, 2=Revision, 3=Pending',
  PRIMARY KEY (`id`),
  KEY `student_username` (`student_username`),
  KEY `scholarship_id` (`scholarship_id`),
  KEY `fk_app_referrer_username` (`referrer_username`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE,
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_referrer_username` FOREIGN KEY (`referrer_username`) REFERENCES `users` (`username`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `applications`
INSERT INTO `applications` VALUES('24','a1125544','1','2025-12-25','112','下',NULL,NULL,NULL,'0','','自有','校內宿舍','0','無','',NULL,'','123','123','',NULL,'','1','2025-12-25 23:32:35','alumni_association','2025-12-25 21:04:11','2025-12-25 23:32:35','1');
INSERT INTO `applications` VALUES('25','a1125544','1','2025-12-25','112','下',NULL,NULL,NULL,'1','','自有','住家','0','身心障礙','胡詠瀚',NULL,'','223','223','蟹從峰','a1125525','指導教授','','2025-12-25 22:10:50','alumni_association','2025-12-25 21:04:47','2025-12-25 22:10:50','1');
INSERT INTO `applications` VALUES('26','a1125544','1','2025-12-26','113','1','0900000000','a1125544@gmail.com.tw','sc','0','[\"..\\/uploads\\/bio_a1125544_1767082264_0.pdf\"]','自有','住家','0','中低收入戶','',NULL,'[\"..\\/uploads\\/other_a1125544_1767082264_0.pdf\"]','jkh','jknk','',NULL,'','check','2025-12-29 20:20:51','alumni_association','2025-12-26 01:13:34','2025-12-30 16:11:04','3');
INSERT INTO `applications` VALUES('27','a1125532','1','2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','sc','1','[\"..\\/uploads\\/bio_a1125532_1767081010_0.pdf\"]','自有','住家','0','低收入戶','',NULL,'','es','sfes','蟹從峰','a1125525','1565','','2025-12-30 16:21:38','alumni_association','2025-12-30 15:50:10','2025-12-30 16:21:38','2');
INSERT INTO `applications` VALUES('28','a1125532','2','2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','sce','0','[\"..\\/uploads\\/bio_a1125532_1767081196_0.pdf\"]','自有','校內宿舍','0','無','',NULL,'','zcds','zv','',NULL,'','pdf','2025-12-30 15:54:32','cs_dept','2025-12-30 15:50:45','2025-12-30 15:54:32','2');
INSERT INTO `applications` VALUES('30','a1125531','1','2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','zc','0','[\"..\\/uploads\\/bio_a1125531_1767082780_0.pdf\"]','租賃','住家','0','無','',NULL,'','ZS','szcz','',NULL,'',NULL,NULL,'alumni_association','2025-12-30 16:19:40','2025-12-30 16:19:40','3');
INSERT INTO `applications` VALUES('31','a1125532','3','2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','sd','1','[\"..\\/uploads\\/bio_a1125532_1767087147_0.pdf\"]','自有','住家','0','身心障礙','',NULL,'','sc','szc','蟹從峰','a1125525','專題指導教授',NULL,NULL,'rd_office','2025-12-30 17:32:27','2025-12-30 17:32:27','3');

-- Table structure for `backup_jobs`
DROP TABLE IF EXISTS `backup_jobs`;
CREATE TABLE `backup_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_name` varchar(120) NOT NULL,
  `status` enum('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
  `requested_by` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_backup_jobs_status_created` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `backup_jobs`
INSERT INTO `backup_jobs` VALUES('3','backup_job_20260531_181005','failed','333',NULL,'伺服器未啟用 ZipArchive，無法產生 ZIP 備份檔。','2026-06-01 00:10:05','2026-06-01 00:10:05');
INSERT INTO `backup_jobs` VALUES('4','backup_job_20260531_181125','running','333',NULL,'備份建立中。','2026-06-01 00:11:25','2026-06-01 00:11:25');

-- Table structure for `data_archives`
DROP TABLE IF EXISTS `data_archives`;
CREATE TABLE `data_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `archive_name` varchar(120) NOT NULL,
  `source_table` varchar(80) NOT NULL,
  `record_count` int(11) NOT NULL DEFAULT 0,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_data_archives_source_created` (`source_table`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `data_archives`

-- Table structure for `departments`
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `budget` decimal(15,0) NOT NULL DEFAULT 1000000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `departments`
INSERT INTO `departments` VALUES('1','電機工程學系','工學院','1000000');
INSERT INTO `departments` VALUES('2','土木與環境工程學系','工學院','1000000');
INSERT INTO `departments` VALUES('3','化學工程及材料工程學系','工學院','1000000');
INSERT INTO `departments` VALUES('4','資訊工程學系','工學院','50000');
INSERT INTO `departments` VALUES('5','應用數學系','理學院','1000000');
INSERT INTO `departments` VALUES('6','生命科學系','理學院','1000000');
INSERT INTO `departments` VALUES('7','應用化學系','理學院','1000000');
INSERT INTO `departments` VALUES('8','應用物理學系','理學院','1000000');
INSERT INTO `departments` VALUES('9','應用經濟學系','管理學院','1000000');
INSERT INTO `departments` VALUES('10','亞太工商管理學系','管理學院','10000');
INSERT INTO `departments` VALUES('11','財務金融學系','管理學院','1000000');
INSERT INTO `departments` VALUES('12','資訊管理學系','管理學院','1000000');
INSERT INTO `departments` VALUES('13','法律學系','法學院','1000000');
INSERT INTO `departments` VALUES('14','政治法律學系','法學院','1000000');
INSERT INTO `departments` VALUES('15','財經法律學系','法學院','1000000');
INSERT INTO `departments` VALUES('16','西洋語文學系','人文社會科學院','1000000');
INSERT INTO `departments` VALUES('17','運動健康與休閒學系','人文社會科學院','1000000');
INSERT INTO `departments` VALUES('18','工藝與創意設計學系','人文社會科學院','1000000');
INSERT INTO `departments` VALUES('19','東亞語文學系','人文社會科學院','1000000');
INSERT INTO `departments` VALUES('20','建築學系','人文社會科學院','1000000');

-- Table structure for `grades`
DROP TABLE IF EXISTS `grades`;
CREATE TABLE `grades` (
  `student_username` varchar(50) NOT NULL COMMENT '學生帳號',
  `academic_year` varchar(10) NOT NULL COMMENT '學年',
  `semester` varchar(10) NOT NULL COMMENT '學期',
  `avg_score` decimal(5,2) DEFAULT NULL COMMENT '平均成績',
  `gpa` decimal(4,2) DEFAULT NULL COMMENT 'GPA',
  `class_rank` int(11) DEFAULT NULL COMMENT '班排',
  `class_size` int(11) DEFAULT NULL COMMENT '全班人數',
  PRIMARY KEY (`student_username`,`academic_year`,`semester`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `grades`
INSERT INTO `grades` VALUES('a1125544','111','上','90.10','4.00','1','45');
INSERT INTO `grades` VALUES('a1125544','111','下','86.20','3.85','5','45');
INSERT INTO `grades` VALUES('a1125544','112','上','88.50','3.92','3','45');

-- Table structure for `homepage_announcements`
DROP TABLE IF EXISTS `homepage_announcements`;
CREATE TABLE `homepage_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '公告標題',
  `content` text DEFAULT NULL COMMENT '公告內容',
  `display_date` date DEFAULT NULL COMMENT '顯示日期',
  `status_label` varchar(50) DEFAULT NULL COMMENT '狀態標籤文字 (例如：進行中、公告)',
  `status_type` varchar(20) DEFAULT NULL COMMENT '狀態樣式：active/notice/warning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `homepage_announcements`
INSERT INTO `homepage_announcements` VALUES('1','114學年度上學期獎學金申請開跑','本學期各項獎學金申請作業即日起開放受理，符合資格之同學請於 10/30 前完成線上申請。','2025-10-01','進行中','active','2026-05-27 19:14:12');
INSERT INTO `homepage_announcements` VALUES('2','傑出學術研究獎獲獎名單公告','恭喜 50 位獲獎同學，完整獲獎名單請至學生事務處生活輔導組查看。','2025-01-13','快訊','notice','2026-05-27 19:14:12');
INSERT INTO `homepage_announcements` VALUES('3','系統維護通知','本系統將於週日凌晨 00:00 至 04:00 進行例行性維護，請避免於該時段操作。','2025-02-01','公告','warning','2026-05-27 19:14:12');

-- Table structure for `issue_reports`
DROP TABLE IF EXISTS `issue_reports`;
CREATE TABLE `issue_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_username` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','processing','resolved') NOT NULL DEFAULT 'open',
  `handled_by` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_issue_reports_status_created` (`status`,`created_at`),
  KEY `idx_issue_reports_reporter` (`reporter_username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `issue_reports`
INSERT INTO `issue_reports` VALUES('1','System Tester','申請流程測試問題','測試用問題回報，可用於驗證 open/processing/resolved 狀態切換。','processing','admin','2026-05-31 23:37:59','2026-05-31 23:54:12');

-- Table structure for `reference_letters`
DROP TABLE IF EXISTS `reference_letters`;
CREATE TABLE `reference_letters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_username` varchar(50) NOT NULL COMMENT '老師帳號',
  `application_id` int(11) NOT NULL COMMENT '申請編號',
  `content` text DEFAULT NULL COMMENT '推薦內容',
  `file_path` varchar(255) DEFAULT NULL COMMENT '推薦信附件路徑',
  `status` varchar(20) DEFAULT 'submitted' COMMENT '狀態: draft, submitted',
  `filled_at` date NOT NULL DEFAULT curdate() COMMENT '填寫日期',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recommendation` (`teacher_username`,`application_id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `reference_letters_ibfk_1` FOREIGN KEY (`teacher_username`) REFERENCES `teachers` (`username`) ON DELETE CASCADE,
  CONSTRAINT `reference_letters_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `reference_letters`
INSERT INTO `reference_letters` VALUES('7','a1125525','25','加油',NULL,'1','2025-12-29');
INSERT INTO `reference_letters` VALUES('8','a1125525','27','vsvwea',NULL,'1','2025-12-30');

-- Table structure for `restore_logs`
DROP TABLE IF EXISTS `restore_logs`;
CREATE TABLE `restore_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_job_id` int(11) DEFAULT NULL,
  `restored_by` varchar(50) DEFAULT NULL,
  `status` enum('started','completed','failed') NOT NULL DEFAULT 'started',
  `message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_restore_logs_job` (`backup_job_id`),
  CONSTRAINT `restore_logs_backup_job_fk` FOREIGN KEY (`backup_job_id`) REFERENCES `backup_jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `restore_logs`

-- Table structure for `review_records`
DROP TABLE IF EXISTS `review_records`;
CREATE TABLE `review_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL COMMENT '申請編號',
  `review_date` date NOT NULL DEFAULT curdate() COMMENT '審查日期',
  `result` varchar(50) DEFAULT NULL COMMENT '審查結果',
  `note` text DEFAULT NULL COMMENT '備註',
  `admin_username` varchar(50) NOT NULL COMMENT '系統管理員帳號',
  PRIMARY KEY (`id`),
  KEY `review_records_application_fk` (`application_id`),
  KEY `review_records_user_fk` (`admin_username`),
  CONSTRAINT `review_records_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_records_user_fk` FOREIGN KEY (`admin_username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `review_records`
INSERT INTO `review_records` VALUES('74','24','2025-12-25','1','1','alumni_association');
INSERT INTO `review_records` VALUES('75','24','2025-12-25','2','1','alumni_association');
INSERT INTO `review_records` VALUES('76','24','2025-12-25','0','1','alumni_association');
INSERT INTO `review_records` VALUES('77','25','2025-12-25','1','','alumni_association');
INSERT INTO `review_records` VALUES('78','24','2025-12-25','1','1','alumni_association');
INSERT INTO `review_records` VALUES('79','24','2025-12-25','2','1','alumni_association');
INSERT INTO `review_records` VALUES('80','24','2025-12-25','0','1','alumni_association');
INSERT INTO `review_records` VALUES('81','24','2025-12-25','2','1','alumni_association');
INSERT INTO `review_records` VALUES('82','25','2025-12-25','2','','alumni_association');
INSERT INTO `review_records` VALUES('83','24','2025-12-25','0','1','alumni_association');
INSERT INTO `review_records` VALUES('84','25','2025-12-25','1','','alumni_association');
INSERT INTO `review_records` VALUES('85','24','2025-12-25','2','1','alumni_association');
INSERT INTO `review_records` VALUES('86','24','2025-12-25','1','1','alumni_association');
INSERT INTO `review_records` VALUES('87','26','2025-12-26','2','','alumni_association');
INSERT INTO `review_records` VALUES('88','28','2025-12-30','2','','cs_dept');
INSERT INTO `review_records` VALUES('89','28','2025-12-30','2','pdf','cs_dept');
INSERT INTO `review_records` VALUES('90','28','2025-12-30','1','pdf','cs_dept');
INSERT INTO `review_records` VALUES('91','28','2025-12-30','0','pdf','cs_dept');
INSERT INTO `review_records` VALUES('92','28','2025-12-30','2','pdf','cs_dept');
INSERT INTO `review_records` VALUES('93','27','2025-12-30','2','','alumni_association');

-- Table structure for `scholarship_eligibility_rules`
DROP TABLE IF EXISTS `scholarship_eligibility_rules`;
CREATE TABLE `scholarship_eligibility_rules` (
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

-- Dumping data for table `scholarship_eligibility_rules`
INSERT INTO `scholarship_eligibility_rules` VALUES('1','3.50','85.00',NULL,NULL,NULL,'提供予家境清寒且學業成績優異之學生');
INSERT INTO `scholarship_eligibility_rules` VALUES('2',NULL,NULL,'10.00','[\"資訊工程學系\"]','資訊工程學系','限各系學生申請，學業成績需達班排前 10%');
INSERT INTO `scholarship_eligibility_rules` VALUES('3','3.80','90.00',NULL,NULL,NULL,'獎勵發表頂尖期刊論文之學生');
INSERT INTO `scholarship_eligibility_rules` VALUES('4','3.00','80.00',NULL,NULL,NULL,'補助赴海外交換學生之機票與生活費');
INSERT INTO `scholarship_eligibility_rules` VALUES('5',NULL,'60.00',NULL,NULL,NULL,'弱勢學生生活津貼，前一學期成績須達 60 分以上');

-- Table structure for `scholarship_units`
DROP TABLE IF EXISTS `scholarship_units`;
CREATE TABLE `scholarship_units` (
  `username` varchar(50) NOT NULL,
  `unit_name` varchar(100) DEFAULT NULL COMMENT '名稱 (單位名稱)',
  `person_in_charge` varchar(100) DEFAULT NULL COMMENT '負責人',
  PRIMARY KEY (`username`),
  CONSTRAINT `scholarship_units_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='獎助單位詳細資料';

-- Dumping data for table `scholarship_units`
INSERT INTO `scholarship_units` VALUES('444','政府','王哈哈');
INSERT INTO `scholarship_units` VALUES('alumni_association','國立高雄大學校友總會',NULL);
INSERT INTO `scholarship_units` VALUES('cs_dept','資訊工程系',NULL);
INSERT INTO `scholarship_units` VALUES('intl_office','國際事務處',NULL);
INSERT INTO `scholarship_units` VALUES('rd_office','研發處',NULL);
INSERT INTO `scholarship_units` VALUES('sa_office','生活輔導組',NULL);
INSERT INTO `scholarship_units` VALUES('test',NULL,NULL);

-- Table structure for `scholarships`
DROP TABLE IF EXISTS `scholarships`;
CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '獎學金名稱',
  `provider_username` varchar(50) NOT NULL COMMENT '發布單位帳號',
  `description` text DEFAULT NULL COMMENT '獎學金描述/申請資格',
  `amount` varchar(100) DEFAULT NULL COMMENT '獎助金額',
  `quota` int(11) DEFAULT 0 COMMENT '名額',
  `application_start_date` date DEFAULT NULL COMMENT '申請開始日期',
  `application_end_date` date DEFAULT NULL COMMENT '申請截止日期',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否啟用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `provider_username` (`provider_username`),
  CONSTRAINT `scholarships_ibfk_1` FOREIGN KEY (`provider_username`) REFERENCES `scholarship_units` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `scholarships`
INSERT INTO `scholarships` VALUES('1','優秀清寒校友獎學金','alumni_association','提供予家境清寒且學業成績優異之學生。','20000','10','2024-02-01','2026-06-30','1','2025-12-20 17:03:31');
INSERT INTO `scholarships` VALUES('2','各系專屬獎學金','cs_dept','限各系學生申請，學業成績需達班排前 10%。','10000','5','2024-02-01','2026-05-28','1','2025-12-20 17:03:31');
INSERT INTO `scholarships` VALUES('3','學術研究績優獎勵','rd_office','獎勵發表頂尖期刊論文之學生。','30000','3','2024-02-01','2026-06-30','1','2025-12-20 17:03:31');
INSERT INTO `scholarships` VALUES('4','海外交換學生獎學金','intl_office','補助赴海外交換學生之機票與生活費。','50000','8','2024-02-01','2026-06-30','1','2025-12-20 17:03:31');
INSERT INTO `scholarships` VALUES('5','弱勢學生生活助學金','sa_office','提供弱勢學生生活津貼，需參與校內服務學習時數 (每週 6 小時)。前一學期成績須達 60 分以上。','6000','20','2024-02-01','2026-06-30','1','2025-12-20 17:03:31');
INSERT INTO `scholarships` VALUES('35','10/27course','alumni_association','svaevs','500000','2','2025-12-30','2025-12-30','1','2025-12-30 22:09:35');

-- Table structure for `student_notifications`
DROP TABLE IF EXISTS `student_notifications`;
CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_username` varchar(50) NOT NULL COMMENT '學生帳號',
  `type` varchar(50) NOT NULL COMMENT 'result_approved/result_rejected/result_revision/deadline_reminder/eligibility_recommendation',
  `title` varchar(255) NOT NULL COMMENT '通知標題',
  `message` text NOT NULL COMMENT '通知內容',
  `related_application_id` int(11) DEFAULT NULL COMMENT '關聯申請編號',
  `related_scholarship_id` int(11) DEFAULT NULL COMMENT '關聯獎學金編號',
  `dedup_key` varchar(255) NOT NULL COMMENT '去重用鍵值',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已讀',
  `email_sent_at` datetime DEFAULT NULL COMMENT 'Email 寄送成功時間',
  `email_last_error` varchar(255) DEFAULT NULL COMMENT '最近一次 Email 錯誤',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_notification_dedup` (`dedup_key`),
  KEY `idx_student_notifications_user_read` (`student_username`,`is_read`,`created_at`),
  KEY `idx_student_notifications_application` (`related_application_id`),
  KEY `idx_student_notifications_scholarship` (`related_scholarship_id`),
  CONSTRAINT `student_notifications_application_fk` FOREIGN KEY (`related_application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_notifications_scholarship_fk` FOREIGN KEY (`related_scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_notifications_student_fk` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生站內通知';

-- Dumping data for table `student_notifications`
INSERT INTO `student_notifications` VALUES('9','a1125532','result_revision','審查結果：需補件','您的「優秀清寒校友獎學金」申請需補件：請檢查缺漏資料','27','1','result-revision-27','0','2026-05-28 00:03:12',NULL,'2026-05-27 19:35:22');
INSERT INTO `student_notifications` VALUES('11','a1125544','result_approved','審查結果：已通過','恭喜您！您申請的「優秀清寒校友獎學金」已通過審核，預計於下個月撥款。','24','1','result-approved-24','1','2026-05-28 00:06:38',NULL,'2026-05-27 19:38:10');
INSERT INTO `student_notifications` VALUES('12','a1125544','result_approved','審查結果：已通過','恭喜您！您申請的「優秀清寒校友獎學金」已通過審核，預計於下個月撥款。','25','1','result-approved-25','1','2026-05-28 00:06:42',NULL,'2026-05-27 19:38:10');
INSERT INTO `student_notifications` VALUES('13','a1125544','eligibility_recommendation','為您推薦獎學金','依您的系所與成績，建議申請「各系專屬獎學金」。系所符合：資訊工程學系；班排 3/45，符合前 10%；限各系學生申請，學業成績需達班排前 10%',NULL,'2','recommendation-2','1','2026-05-28 00:06:49',NULL,'2026-05-27 19:38:10');
INSERT INTO `student_notifications` VALUES('14','a1125544','eligibility_recommendation','為您推薦獎學金','依您的系所與成績，建議申請「海外交換學生獎學金」。GPA 3.92 達標；平均成績 88.5 達標；補助赴海外交換學生之機票與生活費',NULL,'4','recommendation-4','1','2026-05-28 00:06:53',NULL,'2026-05-27 19:38:10');
INSERT INTO `student_notifications` VALUES('15','a1125544','eligibility_recommendation','為您推薦獎學金','依您的系所與成績，建議申請「弱勢學生生活助學金」。平均成績 88.5 達標；弱勢學生生活津貼，前一學期成績須達 60 分以上',NULL,'5','recommendation-5','1','2026-05-28 00:06:57',NULL,'2026-05-27 19:38:10');
INSERT INTO `student_notifications` VALUES('33','a1125544','deadline_reminder','截止提醒','「各系專屬獎學金」將於 2026-05-28 截止，請把握時間完成申請。',NULL,'2','deadline-2-2026-05-28','0','2026-05-28 00:06:45',NULL,'2026-05-27 20:51:18');
INSERT INTO `student_notifications` VALUES('78','a1125532','result_revision','審查結果：需補件','您的「各系專屬獎學金」申請需補件：pdf','28','2','result-revision-28','0','2026-05-28 00:05:08',NULL,'2026-05-28 00:05:04');

-- Table structure for `students`
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `username` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL COMMENT '系所',
  `gender` varchar(10) DEFAULT NULL COMMENT '性別',
  `grade_level` varchar(50) DEFAULT NULL COMMENT '系級 (例如: 大三, 碩一)',
  `class_name` varchar(50) DEFAULT NULL COMMENT '班級 (例如: 甲班)',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `application_history` text DEFAULT NULL COMMENT '申請紀錄',
  PRIMARY KEY (`username`),
  CONSTRAINT `students_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生詳細資料';

-- Dumping data for table `students`
INSERT INTO `students` VALUES('111','西洋語文學系',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `students` VALUES('a1125531','資訊工程學系',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `students` VALUES('a1125532','財經法律學系','男','116','55','',NULL);
INSERT INTO `students` VALUES('a1125544','資訊工程學系','男','大三','資工A','東勢里14鄰健康路183巷8弄32號',NULL);
INSERT INTO `students` VALUES('a11255444','資訊工程學系',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `students` VALUES('a112554444','資訊管理學系',NULL,NULL,NULL,NULL,NULL);

-- Table structure for `system_admins`
DROP TABLE IF EXISTS `system_admins`;
CREATE TABLE `system_admins` (
  `username` varchar(50) NOT NULL,
  `office` varchar(100) DEFAULT NULL COMMENT '處室',
  PRIMARY KEY (`username`),
  CONSTRAINT `system_admins_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統管理員詳細資料';

-- Dumping data for table `system_admins`
INSERT INTO `system_admins` VALUES('333',NULL);
INSERT INTO `system_admins` VALUES('a1125500','教務處');
INSERT INTO `system_admins` VALUES('a1125501',NULL);
INSERT INTO `system_admins` VALUES('admin',NULL);

-- Table structure for `system_logs`
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_role` varchar(50) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `system_logs`
INSERT INTO `system_logs` VALUES('1','System Tester','測試紀錄','这是一条测试日志','2025-12-26 23:35:49');
INSERT INTO `system_logs` VALUES('2','System Admin','新增使用者','新增帳號: 12345123 (學生), 姓名: 12345123','2025-12-26 23:43:09');
INSERT INTO `system_logs` VALUES('3','123','刪除使用者','刪除帳號: 12345123','2025-12-26 23:47:51');
INSERT INTO `system_logs` VALUES('4','System Admin','修改預算','將 亞太工商管理學系 預算更新為 $10,000','2025-12-26 23:48:07');
INSERT INTO `system_logs` VALUES('5','123','新增獎學金','新增項目: 獎懲勳你好 (金額: $2000)','2025-12-26 23:48:39');
INSERT INTO `system_logs` VALUES('6','123','刪除獎學金','刪除項目ID: 33','2025-12-26 23:48:53');
INSERT INTO `system_logs` VALUES('7','123','刪除獎學金','刪除項目ID: 31','2025-12-26 23:48:56');
INSERT INTO `system_logs` VALUES('8','123','系統備份','下載完整備份: backup_scholarship_system_2025-12-26_16-49-05.zip','2025-12-26 23:49:09');
INSERT INTO `system_logs` VALUES('9','System Admin','匯出報表','匯出學系獎學金預算與分配概況報表','2025-12-26 23:55:35');
INSERT INTO `system_logs` VALUES('10','System Admin','新增使用者','新增帳號: 12345123 (學生), 姓名: 12345123','2025-12-26 23:58:57');
INSERT INTO `system_logs` VALUES('11','123','刪除使用者','刪除帳號: 12345123','2025-12-26 23:59:21');
INSERT INTO `system_logs` VALUES('12','System Admin','匯出報表','匯出學系獎學金預算與分配概況報表','2025-12-27 00:24:08');
INSERT INTO `system_logs` VALUES('13','admin','新增獎學金','新增項目: i am rich (金額: $100000000)','2025-12-30 16:26:15');
INSERT INTO `system_logs` VALUES('14','a1125532','新增獎學金','新增項目: 10/27course (金額: $500000)','2025-12-30 22:09:35');
INSERT INTO `system_logs` VALUES('15','admin','更新問題回報','問題 #1 狀態更新為 processing','2026-05-31 23:51:06');
INSERT INTO `system_logs` VALUES('16','admin','更新問題回報','問題 #1 狀態更新為 resolved','2026-05-31 23:51:08');
INSERT INTO `system_logs` VALUES('17','admin','更新問題回報','問題 #1 狀態更新為 open','2026-05-31 23:51:10');
INSERT INTO `system_logs` VALUES('18','admin','更新問題回報','問題 #1 狀態更新為 processing','2026-05-31 23:54:12');
INSERT INTO `system_logs` VALUES('19','admin','建立備份工作','建立備份工作 #1: backup_job_20260531_175542','2026-05-31 23:55:42');
INSERT INTO `system_logs` VALUES('20','admin','建立備份工作','建立備份工作 #2: backup_job_20260531_180405','2026-06-01 00:04:05');

-- Table structure for `teachers`
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL COMMENT '科系',
  `position` varchar(50) DEFAULT NULL COMMENT '職位',
  PRIMARY KEY (`username`),
  UNIQUE KEY `id` (`id`),
  CONSTRAINT `teachers_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='老師詳細資料';

-- Dumping data for table `teachers`
INSERT INTO `teachers` VALUES('21','222','西洋語文學系',NULL);
INSERT INTO `teachers` VALUES('1','a1125525','資訊工程學系',NULL);
INSERT INTO `teachers` VALUES('20','A11255255','資訊管理學系',NULL);

-- Table structure for `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `username` varchar(50) NOT NULL,
  `role` varchar(20) NOT NULL COMMENT '種類: 學生/老師/系管/獎助單位',
  `real_name` varchar(50) NOT NULL COMMENT '姓名',
  `password` varchar(20) NOT NULL COMMENT '密碼',
  `phone` varchar(20) DEFAULT NULL COMMENT '手機',
  `email` varchar(100) DEFAULT NULL COMMENT 'email',
  `avatar_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES('111','學生','哇哇哇','123','0900000000','a1125532@mail.nuk.edu.tw',NULL);
INSERT INTO `users` VALUES('222','老師','哇哇哇','123','0900000000','a1125532@mail.nuk.edu.tw',NULL);
INSERT INTO `users` VALUES('333','系統管理員','哇哇哇','123','0900000000','a1125532@mail.nuk.edu.tw',NULL);
INSERT INTO `users` VALUES('444','獎助單位','政府','123','0900000000','a1125532@mail.nuk.edu.tw',NULL);
INSERT INTO `users` VALUES('a1125500','系統管理員','獎懲勳','1234','0900000002','a1125500@gmail.com',NULL);
INSERT INTO `users` VALUES('a1125501','系統管理員','a1125501','1234','0900000000','a1125501@gmail.com',NULL);
INSERT INTO `users` VALUES('a1125525','老師','蟹從峰','1234','0900000001','a1125525@gmail.com',NULL);
INSERT INTO `users` VALUES('A11255255','老師','薛從峰','1234','0952095209','A11255255@G',NULL);
INSERT INTO `users` VALUES('a1125531','學生','黃呵呵','1234','0908399535','a1125532@mail.nuk.edu.tw',NULL);
INSERT INTO `users` VALUES('a1125532','學生','吳茹婷','ting2005','0908399535','a1125532@mail.nuk.edu.tw',NULL);
INSERT INTO `users` VALUES('a1125544','學生','胡詠瀚','1234','0900000000','a1125544@gmail.com.tw','uploads/avatars/a1125544_1766320941.png');
INSERT INTO `users` VALUES('a11255444','學生','古永漢','1234','0900000000','a11255444@G',NULL);
INSERT INTO `users` VALUES('a112554444','學生','朱永漢','1234','0900000000','a112554444@G',NULL);
INSERT INTO `users` VALUES('admin','系統管理員','系統管理員','1234',NULL,'admin@example.com',NULL);
INSERT INTO `users` VALUES('alumni_association','獎助單位','校友總會','1234',NULL,NULL,NULL);
INSERT INTO `users` VALUES('cs_dept','獎助單位','資工系','1234',NULL,NULL,NULL);
INSERT INTO `users` VALUES('intl_office','獎助單位','國際處','1234',NULL,NULL,NULL);
INSERT INTO `users` VALUES('rd_office','獎助單位','研發處','1234',NULL,NULL,NULL);
INSERT INTO `users` VALUES('sa_office','獎助單位','生活輔導組','1234',NULL,NULL,NULL);
INSERT INTO `users` VALUES('test','獎助單位','test','1234','0900000000','test@gmail.com',NULL);

SET FOREIGN_KEY_CHECKS=1;
