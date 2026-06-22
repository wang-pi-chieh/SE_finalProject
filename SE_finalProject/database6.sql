-- MySQL dump 10.13  Distrib 8.4.0, for macos13.2 (arm64)
--
-- Host: 127.0.0.1    Database: se_finalproject
-- ------------------------------------------------------
-- Server version	8.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `application_drafts`
--

DROP TABLE IF EXISTS `application_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_drafts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `draft_key` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `scholarship_id` int DEFAULT NULL,
  `application_id` int DEFAULT NULL,
  `draft_data` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT 'JSON form field snapshot saved by autosave.js',
  `file_metadata` longtext COLLATE utf8mb4_general_ci COMMENT 'JSON file-name snapshot; files are not uploaded until submit',
  `last_error` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_application_draft_user_key` (`student_username`,`draft_key`),
  KEY `idx_application_drafts_student_updated` (`student_username`,`updated_at`),
  KEY `idx_application_drafts_application` (`application_id`),
  KEY `idx_application_drafts_scholarship` (`scholarship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_drafts`
--

LOCK TABLES `application_drafts` WRITE;
/*!40000 ALTER TABLE `application_drafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `application_drafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '申請編號',
  `student_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '申請學生帳號',
  `scholarship_id` int NOT NULL COMMENT '申請獎學金編號',
  `application_date` date NOT NULL DEFAULT (curdate()) COMMENT '申請日期',
  `academic_year` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '學年 (e.g., 112)',
  `semester` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '學期 (e.g., 1=上, 2=下)',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommendation_required` tinyint(1) DEFAULT '0' COMMENT '是否需要推薦信',
  `biography` text COLLATE utf8mb4_unicode_ci COMMENT '自傳',
  `family_housing_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '家庭居住狀況',
  `personal_housing_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '個人居住狀況',
  `has_student_loan` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '是否就學貸款 (是/否)',
  `tuition_waiver` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '學雜費減免身分',
  `previous_scholarship_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '上學期獲獎助學金名稱',
  `proof_documents` text COLLATE utf8mb4_unicode_ci COMMENT '證明文件路徑 (JSON 或逗號分隔)',
  `application_documents` text COLLATE utf8mb4_unicode_ci COMMENT '檢附申請文件路徑',
  `family_situation_desc` text COLLATE utf8mb4_unicode_ci COMMENT '家庭狀況說明',
  `family_members_desc` text COLLATE utf8mb4_unicode_ci COMMENT '家庭成員狀況',
  `referrer_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推薦人姓名 (若有)',
  `referrer_username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer_relationship` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '與推薦人關係',
  `review_comment` text COLLATE utf8mb4_unicode_ci COMMENT '審查意見',
  `review_score` decimal(5,2) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL COMMENT '審查時間',
  `reviewed_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '審查人員',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` int NOT NULL DEFAULT '3' COMMENT '0=Rejected, 1=Approved, 2=Revision, 3=Pending',
  PRIMARY KEY (`id`),
  KEY `student_username` (`student_username`),
  KEY `scholarship_id` (`scholarship_id`),
  KEY `fk_app_referrer_username` (`referrer_username`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE,
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_referrer_username` FOREIGN KEY (`referrer_username`) REFERENCES `users` (`username`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
INSERT INTO `applications` VALUES (24,'a1125544',1,'2025-12-25','112','下',NULL,NULL,NULL,0,'','自有','校內宿舍','0','無','',NULL,'','123','123','',NULL,'','1',NULL,'2025-12-25 15:32:35','alumni_association','2025-12-25 13:04:11','2025-12-25 15:32:35',1),(25,'a1125544',1,'2025-12-25','112','下',NULL,NULL,NULL,1,'','自有','住家','0','身心障礙','胡詠瀚',NULL,'','223','223','蟹從峰','a1125525','指導教授','',NULL,'2025-12-25 14:10:50','alumni_association','2025-12-25 13:04:47','2025-12-25 14:10:50',1),(26,'a1125544',1,'2025-12-26','113','1','0900000000','a1125544@gmail.com.tw','sc',0,'[\"..\\/uploads\\/bio_a1125544_1767082264_0.pdf\"]','自有','住家','0','中低收入戶','',NULL,'[\"..\\/uploads\\/other_a1125544_1767082264_0.pdf\"]','jkh','jknk','',NULL,'','check',NULL,'2025-12-29 12:20:51','alumni_association','2025-12-25 17:13:34','2025-12-30 08:11:04',3),(27,'a1125532',1,'2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','sc',1,'[\"..\\/uploads\\/bio_a1125532_1767081010_0.pdf\"]','自有','住家','0','低收入戶','',NULL,'','es','sfes','蟹從峰','a1125525','1565','',NULL,'2025-12-30 08:21:38','alumni_association','2025-12-30 07:50:10','2025-12-30 08:21:38',2),(28,'a1125532',2,'2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','sce',0,'[\"..\\/uploads\\/bio_a1125532_1767081196_0.pdf\"]','自有','校內宿舍','0','無','',NULL,'','zcds','zv','',NULL,'','pdf',NULL,'2025-12-30 07:54:32','cs_dept','2025-12-30 07:50:45','2025-12-30 07:54:32',2),(30,'a1125531',1,'2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','zc',0,'[\"..\\/uploads\\/bio_a1125531_1767082780_0.pdf\"]','租賃','住家','0','無','',NULL,'','ZS','szcz','',NULL,'',NULL,NULL,NULL,'alumni_association','2025-12-30 08:19:40','2025-12-30 08:19:40',3),(31,'a1125532',3,'2025-12-30','113','1','0908399535','a1125532@mail.nuk.edu.tw','sd',1,'[\"..\\/uploads\\/bio_a1125532_1767087147_0.pdf\"]','自有','住家','0','身心障礙','',NULL,'','sc','szc','蟹從峰','a1125525','專題指導教授',NULL,NULL,NULL,'rd_office','2025-12-30 09:32:27','2025-12-30 09:32:27',3);
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `award_disbursements`
--

DROP TABLE IF EXISTS `award_disbursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `award_disbursements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `application_id` int NOT NULL,
  `status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `handled_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_award_disbursements_application` (`application_id`),
  KEY `idx_award_disbursements_status_updated` (`status`,`updated_at`),
  KEY `idx_award_disbursements_handler` (`handled_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `award_disbursements`
--

LOCK TABLES `award_disbursements` WRITE;
/*!40000 ALTER TABLE `award_disbursements` DISABLE KEYS */;
INSERT INTO `award_disbursements` VALUES (1,24,'pending',NULL,NULL,NULL,'2026-06-20 19:27:30','2026-06-20 19:27:30'),(2,25,'pending',NULL,NULL,NULL,'2026-06-20 19:27:30','2026-06-20 19:27:30');
/*!40000 ALTER TABLE `award_disbursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_jobs`
--

DROP TABLE IF EXISTS `backup_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('queued','running','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `requested_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_backup_jobs_status_created` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_jobs`
--

LOCK TABLES `backup_jobs` WRITE;
/*!40000 ALTER TABLE `backup_jobs` DISABLE KEYS */;
INSERT INTO `backup_jobs` VALUES (3,'backup_job_20260531_181005','failed','333',NULL,'伺服器未啟用 ZipArchive，無法產生 ZIP 備份檔。','2026-06-01 00:10:05','2026-06-01 00:10:05'),(4,'backup_job_20260531_181125','completed','333','backups/backup_job_20260531_181125.zip','備份已完成，可下載 ZIP 檔。','2026-06-01 00:11:25','2026-06-01 00:11:27'),(10,'backup_job_20260601_152904','completed','222','backups/backup_job_20260601_152904.zip','備份已完成，可下載 ZIP 檔。','2026-06-01 21:29:04','2026-06-01 21:29:06');
/*!40000 ALTER TABLE `backup_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_archives`
--

DROP TABLE IF EXISTS `data_archives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_archives` (
  `id` int NOT NULL AUTO_INCREMENT,
  `archive_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_table` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_count` int NOT NULL DEFAULT '0',
  `archive_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int NOT NULL DEFAULT '0',
  `downloaded_at` datetime DEFAULT NULL,
  `downloaded_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_deleted_at` datetime DEFAULT NULL,
  `original_deleted_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_deleted_count` int NOT NULL DEFAULT '0',
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_data_archives_source_created` (`source_table`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_archives`
--

LOCK TABLES `data_archives` WRITE;
/*!40000 ALTER TABLE `data_archives` DISABLE KEYS */;
INSERT INTO `data_archives` VALUES (1,'issue_reports_resolved_20260601_033937','issue_reports',6,'archives/issue_reports_resolved_20260601_033937.json',3013,NULL,NULL,NULL,NULL,0,'222','2026-06-01 09:39:37'),(2,'issue_reports_resolved_20260601_035715','issue_reports',6,'archives/issue_reports_resolved_20260601_035715.json',3059,'2026-06-01 20:03:19','student-preview',NULL,NULL,0,'222','2026-06-01 09:57:15'),(6,'issue_reports_resolved_20260601_140425','issue_reports',6,'archives/issue_reports_resolved_20260601_140425.json',3071,'2026-06-01 20:04:28','student-preview',NULL,NULL,0,'student-preview','2026-06-01 20:04:25'),(7,'students_admission_before_111_20260601_140838','users,students',1,'archives/students_admission_before_111_20260601_140838.json',892,'2026-06-01 20:21:26','222',NULL,NULL,0,'222','2026-06-01 20:08:38'),(8,'students_admission_before_111','users,students',1,'archives/students_admission_before_111_20260601_142040.csv',315,'2026-06-01 20:20:44','222',NULL,NULL,0,'222','2026-06-01 20:20:40'),(9,'issue_reports_resolved','issue_reports',7,'archives/issue_reports_resolved_20260601_152923.csv',1140,'2026-06-01 21:29:27','222',NULL,NULL,0,'222','2026-06-01 21:29:23');
/*!40000 ALTER TABLE `data_archives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` decimal(15,0) NOT NULL DEFAULT '1000000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'電機工程學系','工學院',1000000),(2,'土木與環境工程學系','工學院',1000000),(3,'化學工程及材料工程學系','工學院',1000000),(4,'資訊工程學系','工學院',50000),(5,'應用數學系','理學院',1000000),(6,'生命科學系','理學院',1000000),(7,'應用化學系','理學院',1000000),(8,'應用物理學系','理學院',1000000),(9,'應用經濟學系','管理學院',1000000),(10,'亞太工商管理學系','管理學院',10000),(11,'財務金融學系','管理學院',1000000),(12,'資訊管理學系','管理學院',1000000),(13,'法律學系','法學院',1000000),(14,'政治法律學系','法學院',1000000),(15,'財經法律學系','法學院',1000000),(16,'西洋語文學系','人文社會科學院',1000000),(17,'運動健康與休閒學系','人文社會科學院',1000000),(18,'工藝與創意設計學系','人文社會科學院',1000000),(19,'東亞語文學系','人文社會科學院',1000000),(20,'建築學系','人文社會科學院',1000000);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `final_award_results`
--

DROP TABLE IF EXISTS `final_award_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `final_award_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scholarship_id` int NOT NULL,
  `application_id` int NOT NULL,
  `student_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `final_score` decimal(6,2) NOT NULL DEFAULT '0.00',
  `rank_no` int NOT NULL,
  `result` enum('selected','waitlisted','not_selected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waitlisted',
  `generated_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_final_award_application` (`application_id`),
  KEY `idx_final_award_scholarship_rank` (`scholarship_id`,`rank_no`),
  KEY `idx_final_award_result` (`result`,`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `final_award_results`
--

LOCK TABLES `final_award_results` WRITE;
/*!40000 ALTER TABLE `final_award_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `final_award_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grades` (
  `student_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生帳號',
  `academic_year` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學年',
  `semester` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學期',
  `avg_score` decimal(5,2) DEFAULT NULL COMMENT '平均成績',
  `gpa` decimal(4,2) DEFAULT NULL COMMENT 'GPA',
  `class_rank` int DEFAULT NULL COMMENT '班排',
  `class_size` int DEFAULT NULL COMMENT '全班人數',
  PRIMARY KEY (`student_username`,`academic_year`,`semester`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
INSERT INTO `grades` VALUES ('111','111','1',88.00,3.90,3,48),('111','111','2',89.50,4.00,2,48);
INSERT INTO `grades` VALUES ('a1125544','111','上',90.10,4.00,1,45),('a1125544','111','下',86.20,3.85,5,45),('a1125544','112','上',88.50,3.92,3,45);
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homepage_announcements`
--

DROP TABLE IF EXISTS `homepage_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公告標題',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '公告內容',
  `display_date` date DEFAULT NULL COMMENT '顯示日期',
  `status_label` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '狀態標籤文字 (例如：進行中、公告)',
  `status_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '狀態樣式：active/notice/warning',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_announcements`
--

LOCK TABLES `homepage_announcements` WRITE;
/*!40000 ALTER TABLE `homepage_announcements` DISABLE KEYS */;
INSERT INTO `homepage_announcements` VALUES (1,'114學年度上學期獎學金申請開跑','本學期各項獎學金申請作業即日起開放受理，符合資格之同學請於 10/30 前完成線上申請。','2025-10-01','進行中','active','2026-05-27 11:14:12'),(2,'傑出學術研究獎獲獎名單公告','恭喜 50 位獲獎同學，完整獲獎名單請至學生事務處生活輔導組查看。','2025-01-13','快訊','notice','2026-05-27 11:14:12'),(3,'系統維護通知','本系統將於週日凌晨 00:00 至 04:00 進行例行性維護，請避免於該時段操作。','2025-02-01','公告','warning','2026-05-27 11:14:12');
/*!40000 ALTER TABLE `homepage_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issue_report_notifications`
--

DROP TABLE IF EXISTS `issue_report_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issue_report_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `issue_report_id` int NOT NULL,
  `recipient_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `email_sent_at` datetime DEFAULT NULL,
  `email_last_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_issue_notifications_user_read` (`recipient_username`,`is_read`,`created_at`),
  KEY `idx_issue_notifications_report` (`issue_report_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issue_report_notifications`
--

LOCK TABLES `issue_report_notifications` WRITE;
/*!40000 ALTER TABLE `issue_report_notifications` DISABLE KEYS */;
INSERT INTO `issue_report_notifications` VALUES (1,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:37:02'),(2,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 00:37:07'),(3,2,'111','問題回報狀態更新','你提出的問題「嗨」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 00:37:09'),(4,2,'111','問題回報狀態更新','你提出的問題「嗨」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 00:37:13'),(5,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 00:37:16'),(6,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 00:37:20'),(7,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 00:37:24'),(8,1,'System Tester','問題回報狀態更新','你提出的問題「申請流程測試問題」狀態已更新為：待處理',0,NULL,NULL,'2026-06-01 00:37:25'),(9,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:37:27'),(10,1,'System Tester','問題回報狀態更新','你提出的問題「申請流程測試問題」狀態已更新為：已解決',0,NULL,NULL,'2026-06-01 00:37:29'),(11,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 00:40:35'),(12,6,'111','問題回報狀態更新','你提出的問題「3」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:42:39'),(13,5,'111','問題回報狀態更新','你提出的問題「2」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 00:42:51'),(14,4,'111','問題回報狀態更新','你提出的問題「1」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:43:00'),(15,3,'111','問題回報狀態更新','你提出的問題「111」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 00:43:04'),(16,2,'111','問題回報狀態更新','你提出的問題「嗨」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:43:05'),(17,6,'111','問題回報狀態更新','你提出的問題「3」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 00:43:07'),(18,9,'222','問題回報狀態更新','你提出的問題「qqq」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:45:07'),(19,8,'222','問題回報狀態更新','你提出的問題「qq」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 00:45:08'),(20,7,'222','問題回報狀態更新','你提出的問題「q」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:45:09'),(21,9,'222','問題回報狀態更新','你提出的問題「qqq」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 00:45:10'),(22,9,'222','問題回報狀態更新','你提出的問題「qqq」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 00:45:12'),(23,7,'222','問題回報狀態更新','你提出的問題「q」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 00:50:56'),(24,7,'222','問題回報狀態更新','你提出的問題「q」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 01:00:15'),(25,8,'222','問題回報狀態更新','你提出的問題「qq」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 01:00:24'),(26,8,'222','問題回報狀態更新','你提出的問題「qq」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 01:00:27'),(27,9,'222','問題回報狀態更新','你提出的問題「qqq」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 01:00:32'),(28,9,'222','問題回報狀態更新','你提出的問題「qqq」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 01:05:30'),(29,8,'222','問題回報狀態更新','你提出的問題「qq」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 01:05:34'),(30,7,'222','問題回報狀態更新','你提出的問題「q」狀態已更新為：待處理',1,NULL,NULL,'2026-06-01 01:05:43'),(31,8,'222','問題回報狀態更新','你提出的問題「qq」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 01:05:47'),(32,9,'222','問題回報狀態更新','你提出的問題「qqq」狀態已更新為：已解決',1,NULL,NULL,'2026-06-01 01:07:10'),(33,10,'a1125544','問題回報狀態更新','你提出的問題「1」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 12:54:43'),(34,11,'111','問題回報狀態更新','你提出的問題「1」狀態已更新為：處理中',1,NULL,NULL,'2026-06-01 12:57:48'),(35,10,'a1125544','問題回報狀態更新','你提出的問題「1」狀態已更新為：待處理',0,NULL,NULL,'2026-06-01 12:57:59'),(36,10,'a1125544','問題回報狀態更新','你提出的問題「1」狀態已更新為：處理中',0,NULL,NULL,'2026-06-01 12:58:02'),(37,12,'a1125544','問題回報狀態更新','你提出的問題「1」狀態已更新為：處理中',0,NULL,NULL,'2026-06-01 20:29:42'),(38,10,'a1125544','問題回報狀態更新','你提出的問題「1」狀態已更新為：已解決',0,NULL,NULL,'2026-06-01 20:29:46'),(39,13,'222','問題回報狀態更新','你提出的問題「.」狀態已更新為：處理中',0,NULL,NULL,'2026-06-01 20:38:14'),(40,13,'222','問題回報狀態更新','你提出的問題「.」狀態已更新為：待處理',0,NULL,NULL,'2026-06-01 20:38:17');
/*!40000 ALTER TABLE `issue_report_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issue_reports`
--

DROP TABLE IF EXISTS `issue_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issue_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporter_role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('open','processing','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `handled_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_issue_reports_status_created` (`status`,`created_at`),
  KEY `idx_issue_reports_reporter` (`reporter_username`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issue_reports`
--

LOCK TABLES `issue_reports` WRITE;
/*!40000 ALTER TABLE `issue_reports` DISABLE KEYS */;
INSERT INTO `issue_reports` VALUES (1,'System Tester',NULL,'申請流程測試問題','測試用問題回報，可用於驗證 open/processing/resolved 狀態切換。',NULL,NULL,'resolved','111','2026-05-31 23:37:59','2026-06-01 00:37:29'),(2,'111',NULL,'嗨','[角色: 學生]\n你好',NULL,NULL,'processing','111','2026-06-01 00:27:08','2026-06-01 00:43:05'),(3,'111','學生','111','123','a1125532@mail.nuk.edu.tw','0900000000','resolved','111','2026-06-01 00:36:47','2026-06-01 00:43:04'),(4,'111','學生','1','1','a1125532@mail.nuk.edu.tw','0900000000','processing','111','2026-06-01 00:41:40','2026-06-01 00:43:00'),(5,'111','學生','2','2','a1125532@mail.nuk.edu.tw','0900000000','resolved','111','2026-06-01 00:41:45','2026-06-01 00:42:51'),(6,'111','學生','3','3','a1125532@mail.nuk.edu.tw','0900000000','resolved','111','2026-06-01 00:41:58','2026-06-01 00:43:07'),(7,'222','老師','q','q','a1125532@mail.nuk.edu.tw','0900000000','open','222','2026-06-01 00:44:52','2026-06-01 01:05:43'),(8,'222','老師','qq','qq','a1125532@mail.nuk.edu.tw','0900000000','resolved','222','2026-06-01 00:44:58','2026-06-01 01:05:47'),(9,'222','老師','qqq','qqq','a1125532@mail.nuk.edu.tw','0900000000','resolved','222','2026-06-01 00:45:01','2026-06-01 01:07:10'),(10,'a1125544','學生','1','1','a1125544@gmail.com.tw','0900000000','resolved','a1125544','2026-06-01 12:54:30','2026-06-01 20:29:46'),(11,'111','學生','1','1','a1125532@mail.nuk.edu.tw','0900000000','processing','111','2026-06-01 12:57:26','2026-06-01 12:57:48'),(12,'a1125544','學生','1','1','a1125544@gmail.com.tw','0900000000','processing','a1125544','2026-06-01 20:29:31','2026-06-01 20:29:42'),(13,'222','老師','.','.','a1125532@mail.nuk.edu.tw','0900000000','open','222','2026-06-01 20:38:08','2026-06-01 20:38:17'),(14,'222','老師','asd','asd','a1125532@mail.nuk.edu.tw','0900000000','open','222','2026-06-01 20:47:23','2026-06-01 20:47:31');
/*!40000 ALTER TABLE `issue_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mentor_assignments`
--

DROP TABLE IF EXISTS `mentor_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mentor_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parity_rule` enum('odd','even','all') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mentor_assignment` (`teacher_username`,`department`),
  KEY `idx_mentor_department_rule` (`department`,`parity_rule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mentor_assignments`
--

LOCK TABLES `mentor_assignments` WRITE;
/*!40000 ALTER TABLE `mentor_assignments` DISABLE KEYS */;
INSERT INTO `mentor_assignments` VALUES (1,'222','工藝與創意設計學系','all','2026-06-22 11:40:00');
/*!40000 ALTER TABLE `mentor_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mentor_return_records`
--

DROP TABLE IF EXISTS `mentor_return_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mentor_return_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `application_id` int NOT NULL,
  `teacher_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_return_application` (`application_id`),
  KEY `idx_return_teacher` (`teacher_username`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mentor_return_records`
--

LOCK TABLES `mentor_return_records` WRITE;
/*!40000 ALTER TABLE `mentor_return_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `mentor_return_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mentor_scholarship_rules`
--

DROP TABLE IF EXISTS `mentor_scholarship_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mentor_scholarship_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scholarship_id` int NOT NULL,
  `department_filter` text COLLATE utf8mb4_unicode_ci,
  `min_avg_score` decimal(5,2) DEFAULT NULL,
  `max_rank_percent` decimal(5,4) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scholarship_id` (`scholarship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mentor_scholarship_rules`
--

LOCK TABLES `mentor_scholarship_rules` WRITE;
/*!40000 ALTER TABLE `mentor_scholarship_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `mentor_scholarship_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recommendation_templates`
--

DROP TABLE IF EXISTS `recommendation_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recommendation_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recommendation_templates`
--

LOCK TABLES `recommendation_templates` WRITE;
/*!40000 ALTER TABLE `recommendation_templates` DISABLE KEYS */;
INSERT INTO `recommendation_templates` VALUES (1,'general','一般推薦信範本','一般','敬啟者：\n本人為 {student_name}（{student_username}）之導師。該生就讀 {department}，申請 {scholarship_name}。依平時觀察，該生學習態度穩定，具備良好責任感。最近平均成績為 {avg_score}，GPA 為 {gpa}，班排百分比為 {rank_percent}。本人推薦該生申請本獎助學金。\n導師：{teacher_name}',1,'2026-06-20 19:27:30'),(2,'financial_need','清寒學生推薦信範本','清寒','敬啟者：\n本人推薦 {student_name} 申請 {scholarship_name}。該生平時努力向學，雖面臨經濟壓力，仍能維持良好學習態度。最近平均成績為 {avg_score}，班排百分比為 {rank_percent}。懇請獎助單位給予支持。\n導師：{teacher_name}',1,'2026-06-20 19:27:30'),(3,'academic','學術績優推薦信範本','學術','敬啟者：\n{student_name} 於 {department} 表現優良，申請 {scholarship_name}。其最近 GPA 為 {gpa}，平均成績 {avg_score}，具備持續精進與學術發展潛力，故本人予以推薦。\n導師：{teacher_name}',1,'2026-06-20 19:27:30');
/*!40000 ALTER TABLE `recommendation_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reference_letters`
--

DROP TABLE IF EXISTS `reference_letters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reference_letters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '老師帳號',
  `application_id` int NOT NULL COMMENT '申請編號',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '推薦內容',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推薦信附件路徑',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'submitted' COMMENT '狀態: draft, submitted',
  `filled_at` date NOT NULL DEFAULT (curdate()) COMMENT '填寫日期',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recommendation` (`teacher_username`,`application_id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `reference_letters_ibfk_1` FOREIGN KEY (`teacher_username`) REFERENCES `teachers` (`username`) ON DELETE CASCADE,
  CONSTRAINT `reference_letters_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reference_letters`
--

LOCK TABLES `reference_letters` WRITE;
/*!40000 ALTER TABLE `reference_letters` DISABLE KEYS */;
INSERT INTO `reference_letters` VALUES (7,'a1125525',25,'加油',NULL,'1','2025-12-29'),(8,'a1125525',27,'vsvwea',NULL,'1','2025-12-30');
/*!40000 ALTER TABLE `reference_letters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restore_logs`
--

DROP TABLE IF EXISTS `restore_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `restore_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `backup_job_id` int DEFAULT NULL,
  `restored_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('started','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'started',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_restore_logs_job` (`backup_job_id`),
  CONSTRAINT `restore_logs_backup_job_fk` FOREIGN KEY (`backup_job_id`) REFERENCES `backup_jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restore_logs`
--

LOCK TABLES `restore_logs` WRITE;
/*!40000 ALTER TABLE `restore_logs` DISABLE KEYS */;
INSERT INTO `restore_logs` VALUES (1,NULL,'222','started','SQL 檔案已上傳，待管理員至 phpMyAdmin 匯入。','2026-06-01 09:09:11'),(2,NULL,'222','started','SQL 檔案已上傳，待管理員至 phpMyAdmin 匯入。','2026-06-01 21:29:40'),(3,NULL,'222','started','SQL 檔案已上傳，待管理員至 phpMyAdmin 匯入。','2026-06-01 21:29:57');
/*!40000 ALTER TABLE `restore_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restore_uploads`
--

DROP TABLE IF EXISTS `restore_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `restore_uploads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `restore_log_id` int DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL DEFAULT '0',
  `uploaded_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_restore_uploads_created` (`created_at`),
  KEY `idx_restore_uploads_log` (`restore_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restore_uploads`
--

LOCK TABLES `restore_uploads` WRITE;
/*!40000 ALTER TABLE `restore_uploads` DISABLE KEYS */;
INSERT INTO `restore_uploads` VALUES (1,1,'database_dump.sql','restore_sql_20260601_030911_123d283a_database_dump.sql','restore_uploads/restore_sql_20260601_030911_123d283a_database_dump.sql',35642,'222','2026-06-01 09:09:11'),(2,2,'001_wang_issue_backup.sql','restore_sql_20260601_152940_6953487d_001_wang_issue_backup.sql','restore_uploads/restore_sql_20260601_152940_6953487d_001_wang_issue_backup.sql',5297,'222','2026-06-01 21:29:40'),(3,3,'001_wang_issue_backup.sql','restore_sql_20260601_152957_a865672e_001_wang_issue_backup.sql','restore_uploads/restore_sql_20260601_152957_a865672e_001_wang_issue_backup.sql',5297,'222','2026-06-01 21:29:57');
/*!40000 ALTER TABLE `restore_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_records`
--

DROP TABLE IF EXISTS `review_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `application_id` int NOT NULL COMMENT '申請編號',
  `review_date` date NOT NULL DEFAULT (curdate()) COMMENT '審查日期',
  `result` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '審查結果',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT '備註',
  `score` decimal(5,2) DEFAULT NULL,
  `stage` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'initial',
  `admin_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系統管理員帳號',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `review_records_application_fk` (`application_id`),
  KEY `review_records_user_fk` (`admin_username`),
  KEY `idx_review_records_app_stage` (`application_id`,`stage`,`review_date`),
  KEY `idx_review_records_score` (`application_id`,`score`),
  CONSTRAINT `review_records_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_records_user_fk` FOREIGN KEY (`admin_username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_records`
--

LOCK TABLES `review_records` WRITE;
/*!40000 ALTER TABLE `review_records` DISABLE KEYS */;
INSERT INTO `review_records` VALUES (74,24,'2025-12-25','1','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(75,24,'2025-12-25','2','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(76,24,'2025-12-25','0','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(77,25,'2025-12-25','1','',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(78,24,'2025-12-25','1','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(79,24,'2025-12-25','2','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(80,24,'2025-12-25','0','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(81,24,'2025-12-25','2','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(82,25,'2025-12-25','2','',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(83,24,'2025-12-25','0','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(84,25,'2025-12-25','1','',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(85,24,'2025-12-25','2','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(86,24,'2025-12-25','1','1',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(87,26,'2025-12-26','2','',NULL,'initial','alumni_association','2026-06-20 19:27:30'),(88,28,'2025-12-30','2','',NULL,'initial','cs_dept','2026-06-20 19:27:30'),(89,28,'2025-12-30','2','pdf',NULL,'initial','cs_dept','2026-06-20 19:27:30'),(90,28,'2025-12-30','1','pdf',NULL,'initial','cs_dept','2026-06-20 19:27:30'),(91,28,'2025-12-30','0','pdf',NULL,'initial','cs_dept','2026-06-20 19:27:30'),(92,28,'2025-12-30','2','pdf',NULL,'initial','cs_dept','2026-06-20 19:27:30'),(93,27,'2025-12-30','2','',NULL,'initial','alumni_association','2026-06-20 19:27:30');
/*!40000 ALTER TABLE `review_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarship_eligibility_rules`
--

DROP TABLE IF EXISTS `scholarship_eligibility_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scholarship_eligibility_rules` (
  `scholarship_id` int NOT NULL COMMENT '獎學金編號',
  `min_gpa` decimal(4,2) DEFAULT NULL COMMENT '最低 GPA',
  `min_avg_score` decimal(5,2) DEFAULT NULL COMMENT '最低平均成績',
  `max_class_rank_percent` decimal(5,2) DEFAULT NULL COMMENT '班排百分比上限，例如 10 代表前 10%',
  `allowed_departments` text COLLATE utf8mb4_unicode_ci COMMENT '允許系所 JSON 陣列，NULL 代表不限',
  `provider_department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '獎助單位對應系所',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '規則說明',
  PRIMARY KEY (`scholarship_id`),
  CONSTRAINT `scholarship_eligibility_rules_fk` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='獎學金資格比對規則';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarship_eligibility_rules`
--

LOCK TABLES `scholarship_eligibility_rules` WRITE;
/*!40000 ALTER TABLE `scholarship_eligibility_rules` DISABLE KEYS */;
INSERT INTO `scholarship_eligibility_rules` VALUES (1,3.50,85.00,NULL,NULL,NULL,'提供予家境清寒且學業成績優異之學生'),(2,NULL,NULL,10.00,'[\"資訊工程學系\"]','資訊工程學系','限各系學生申請，學業成績需達班排前 10%'),(3,3.80,90.00,NULL,NULL,NULL,'獎勵發表頂尖期刊論文之學生'),(4,3.00,80.00,NULL,NULL,NULL,'補助赴海外交換學生之機票與生活費'),(5,NULL,60.00,NULL,NULL,NULL,'弱勢學生生活津貼，前一學期成績須達 60 分以上');
/*!40000 ALTER TABLE `scholarship_eligibility_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarship_export_logs`
--

DROP TABLE IF EXISTS `scholarship_export_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scholarship_export_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `export_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'scholarships_csv',
  `exported_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `row_count` int NOT NULL DEFAULT '0',
  `file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filters` longtext COLLATE utf8mb4_general_ci COMMENT 'JSON export filters or context',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scholarship_export_logs_created` (`created_at`),
  KEY `idx_scholarship_export_logs_exporter` (`exported_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarship_export_logs`
--

LOCK TABLES `scholarship_export_logs` WRITE;
/*!40000 ALTER TABLE `scholarship_export_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `scholarship_export_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarship_import_batches`
--

DROP TABLE IF EXISTS `scholarship_import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scholarship_import_batches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_filename` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `uploaded_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('uploaded','confirmed','failed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'uploaded',
  `total_rows` int NOT NULL DEFAULT '0',
  `valid_rows` int NOT NULL DEFAULT '0',
  `error_rows` int NOT NULL DEFAULT '0',
  `import_data` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT 'JSON preview rows parsed from CSV before confirmation',
  `error_report` longtext COLLATE utf8mb4_general_ci COMMENT 'JSON validation or import errors',
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scholarship_import_batches_status_created` (`status`,`created_at`),
  KEY `idx_scholarship_import_batches_uploader` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarship_import_batches`
--

LOCK TABLES `scholarship_import_batches` WRITE;
/*!40000 ALTER TABLE `scholarship_import_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `scholarship_import_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarship_units`
--

DROP TABLE IF EXISTS `scholarship_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scholarship_units` (
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '名稱 (單位名稱)',
  `person_in_charge` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '負責人',
  PRIMARY KEY (`username`),
  CONSTRAINT `scholarship_units_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='獎助單位詳細資料';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarship_units`
--

LOCK TABLES `scholarship_units` WRITE;
/*!40000 ALTER TABLE `scholarship_units` DISABLE KEYS */;
INSERT INTO `scholarship_units` VALUES ('444','政府','王哈哈'),('alumni_association','國立高雄大學校友總會',NULL),('cs_dept','資訊工程系',NULL),('intl_office','國際事務處',NULL),('rd_office','研發處',NULL),('reviewer-preview','reviewer-preview','reviewer-preview'),('sa_office','生活輔導組',NULL),('test',NULL,NULL);
/*!40000 ALTER TABLE `scholarship_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarships`
--

DROP TABLE IF EXISTS `scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scholarships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '獎學金名稱',
  `provider_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '發布單位帳號',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '獎學金描述/申請資格',
  `amount` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '獎助金額',
  `quota` int DEFAULT '0' COMMENT '名額',
  `application_start_date` date DEFAULT NULL COMMENT '申請開始日期',
  `application_end_date` date DEFAULT NULL COMMENT '申請截止日期',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否啟用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `provider_username` (`provider_username`),
  CONSTRAINT `scholarships_ibfk_1` FOREIGN KEY (`provider_username`) REFERENCES `scholarship_units` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarships`
--

LOCK TABLES `scholarships` WRITE;
/*!40000 ALTER TABLE `scholarships` DISABLE KEYS */;
INSERT INTO `scholarships` VALUES (1,'優秀清寒校友獎學金','alumni_association','提供予家境清寒且學業成績優異之學生。','20000',10,'2024-02-01','2026-06-30',1,'2025-12-20 09:03:31'),(2,'各系專屬獎學金','cs_dept','限各系學生申請，學業成績需達班排前 10%。','10000',5,'2024-02-01','2026-05-28',1,'2025-12-20 09:03:31'),(3,'學術研究績優獎勵','rd_office','獎勵發表頂尖期刊論文之學生。','30000',3,'2024-02-01','2026-06-30',1,'2025-12-20 09:03:31'),(4,'海外交換學生獎學金','intl_office','補助赴海外交換學生之機票與生活費。','50000',8,'2024-02-01','2026-06-30',1,'2025-12-20 09:03:31'),(5,'弱勢學生生活助學金','sa_office','提供弱勢學生生活津貼，需參與校內服務學習時數 (每週 6 小時)。前一學期成績須達 60 分以上。','6000',20,'2024-02-01','2026-06-30',1,'2025-12-20 09:03:31'),(35,'10/27course','alumni_association','svaevs','500000',2,'2025-12-30','2025-12-30',1,'2025-12-30 14:09:35');
/*!40000 ALTER TABLE `scholarships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_notifications`
--

DROP TABLE IF EXISTS `student_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生帳號',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'result_approved/result_rejected/result_revision/deadline_reminder/eligibility_recommendation',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '通知標題',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '通知內容',
  `related_application_id` int DEFAULT NULL COMMENT '關聯申請編號',
  `related_scholarship_id` int DEFAULT NULL COMMENT '關聯獎學金編號',
  `dedup_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '去重用鍵值',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已讀',
  `email_sent_at` datetime DEFAULT NULL COMMENT 'Email 寄送成功時間',
  `email_last_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '最近一次 Email 錯誤',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_notification_dedup` (`dedup_key`),
  KEY `idx_student_notifications_user_read` (`student_username`,`is_read`,`created_at`),
  KEY `idx_student_notifications_application` (`related_application_id`),
  KEY `idx_student_notifications_scholarship` (`related_scholarship_id`),
  CONSTRAINT `student_notifications_application_fk` FOREIGN KEY (`related_application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_notifications_scholarship_fk` FOREIGN KEY (`related_scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_notifications_student_fk` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生站內通知';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_notifications`
--

LOCK TABLES `student_notifications` WRITE;
/*!40000 ALTER TABLE `student_notifications` DISABLE KEYS */;
INSERT INTO `student_notifications` VALUES (9,'a1125532','result_revision','審查結果：需補件','您的「優秀清寒校友獎學金」申請需補件：請檢查缺漏資料',27,1,'result-revision-27',0,'2026-05-28 00:03:12',NULL,'2026-05-27 11:35:22'),(11,'a1125544','result_approved','審查結果：已通過','恭喜您！您申請的「優秀清寒校友獎學金」已通過審核，預計於下個月撥款。',24,1,'result-approved-24',1,'2026-05-28 00:06:38',NULL,'2026-05-27 11:38:10'),(12,'a1125544','result_approved','審查結果：已通過','恭喜您！您申請的「優秀清寒校友獎學金」已通過審核，預計於下個月撥款。',25,1,'result-approved-25',1,'2026-05-28 00:06:42',NULL,'2026-05-27 11:38:10'),(13,'a1125544','eligibility_recommendation','為您推薦獎學金','依您的系所與成績，建議申請「各系專屬獎學金」。系所符合：資訊工程學系；班排 3/45，符合前 10%；限各系學生申請，學業成績需達班排前 10%',NULL,2,'recommendation-2',1,'2026-05-28 00:06:49',NULL,'2026-05-27 11:38:10'),(14,'a1125544','eligibility_recommendation','為您推薦獎學金','依您的系所與成績，建議申請「海外交換學生獎學金」。GPA 3.92 達標；平均成績 88.5 達標；補助赴海外交換學生之機票與生活費',NULL,4,'recommendation-4',1,'2026-05-28 00:06:53',NULL,'2026-05-27 11:38:10'),(15,'a1125544','eligibility_recommendation','為您推薦獎學金','依您的系所與成績，建議申請「弱勢學生生活助學金」。平均成績 88.5 達標；弱勢學生生活津貼，前一學期成績須達 60 分以上',NULL,5,'recommendation-5',1,'2026-05-28 00:06:57',NULL,'2026-05-27 11:38:10'),(33,'a1125544','deadline_reminder','截止提醒','「各系專屬獎學金」將於 2026-05-28 截止，請把握時間完成申請。',NULL,2,'deadline-2-2026-05-28',0,'2026-05-28 00:06:45',NULL,'2026-05-27 12:51:18'),(78,'a1125532','result_revision','審查結果：需補件','您的「各系專屬獎學金」申請需補件：pdf',28,2,'result-revision-28',0,'2026-05-28 00:05:08',NULL,'2026-05-27 16:05:04'),(113,'a1125544','issue_report_update','問題回報狀態更新','你提出的問題「1」狀態已更新為：處理中',NULL,NULL,'issue-report-12-processing',1,NULL,NULL,'2026-06-01 12:29:42'),(114,'a1125544','issue_report_update','問題回報狀態更新','你提出的問題「1」狀態已更新為：已解決',NULL,NULL,'issue-report-10-resolved',1,NULL,NULL,'2026-06-01 12:29:46');
/*!40000 ALTER TABLE `student_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '系所',
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '性別',
  `grade_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '系級 (例如: 大三, 碩一)',
  `class_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '班級 (例如: 甲班)',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '地址',
  `application_history` text COLLATE utf8mb4_unicode_ci COMMENT '申請紀錄',
  PRIMARY KEY (`username`),
  CONSTRAINT `students_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生詳細資料';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES ('111','工藝與創意設計學系','男','','','',NULL),('a1125531','資訊工程學系',NULL,NULL,NULL,NULL,NULL),('a1125532','財經法律學系','男','116','55','',NULL),('a1125544','資訊工程學系','男','大三','資工A','東勢里14鄰健康路183巷8弄32號',NULL),('a11255444','資訊工程學系',NULL,NULL,NULL,NULL,NULL),('a112554444','資訊管理學系',NULL,NULL,NULL,NULL,NULL),('student-preview','工藝與創意設計學系',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_admins`
--

DROP TABLE IF EXISTS `system_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_admins` (
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `office` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '處室',
  PRIMARY KEY (`username`),
  CONSTRAINT `system_admins_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統管理員詳細資料';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_admins`
--

LOCK TABLES `system_admins` WRITE;
/*!40000 ALTER TABLE `system_admins` DISABLE KEYS */;
INSERT INTO `system_admins` VALUES ('333',NULL),('a1125500','教務處'),('a1125501',NULL),('admin',NULL);
/*!40000 ALTER TABLE `system_admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,'System Tester','測試紀錄','这是一条测试日志','2025-12-26 23:35:49'),(2,'System Admin','新增使用者','新增帳號: 12345123 (學生), 姓名: 12345123','2025-12-26 23:43:09'),(3,'123','刪除使用者','刪除帳號: 12345123','2025-12-26 23:47:51'),(4,'System Admin','修改預算','將 亞太工商管理學系 預算更新為 $10,000','2025-12-26 23:48:07'),(5,'123','新增獎學金','新增項目: 獎懲勳你好 (金額: $2000)','2025-12-26 23:48:39'),(6,'123','刪除獎學金','刪除項目ID: 33','2025-12-26 23:48:53'),(7,'123','刪除獎學金','刪除項目ID: 31','2025-12-26 23:48:56'),(8,'123','系統備份','下載完整備份: backup_scholarship_system_2025-12-26_16-49-05.zip','2025-12-26 23:49:09'),(9,'System Admin','匯出報表','匯出學系獎學金預算與分配概況報表','2025-12-26 23:55:35'),(10,'System Admin','新增使用者','新增帳號: 12345123 (學生), 姓名: 12345123','2025-12-26 23:58:57'),(11,'123','刪除使用者','刪除帳號: 12345123','2025-12-26 23:59:21'),(12,'System Admin','匯出報表','匯出學系獎學金預算與分配概況報表','2025-12-27 00:24:08'),(13,'admin','新增獎學金','新增項目: i am rich (金額: $100000000)','2025-12-30 16:26:15'),(14,'a1125532','新增獎學金','新增項目: 10/27course (金額: $500000)','2025-12-30 22:09:35'),(15,'admin','更新問題回報','問題 #1 狀態更新為 processing','2026-05-31 23:51:06'),(16,'admin','更新問題回報','問題 #1 狀態更新為 resolved','2026-05-31 23:51:08'),(17,'admin','更新問題回報','問題 #1 狀態更新為 open','2026-05-31 23:51:10'),(18,'admin','更新問題回報','問題 #1 狀態更新為 processing','2026-05-31 23:54:12'),(19,'admin','建立備份工作','建立備份工作 #1: backup_job_20260531_175542','2026-05-31 23:55:42'),(20,'admin','建立備份工作','建立備份工作 #2: backup_job_20260531_180405','2026-06-01 00:04:05'),(21,'333','建立備份工作','建立備份工作 #4: backup_job_20260531_181125，已產生 ZIP 備份','2026-06-01 00:11:27'),(22,'333','建立備份工作','建立備份工作 #5: backup_job_20260531_181441，已產生 ZIP 備份','2026-06-01 00:14:42'),(23,'333','建立備份工作','建立備份工作 #6: backup_job_20260531_181444，已產生 ZIP 備份','2026-06-01 00:14:45'),(24,'333','建立備份工作','建立備份工作 #7: backup_job_20260531_181649，已產生 ZIP 備份','2026-06-01 00:16:51'),(25,'333','建立備份工作','建立備份工作 #8: backup_job_20260531_181651，已產生 ZIP 備份','2026-06-01 00:16:52'),(26,'111','更新問題回報','問題 #3 狀態更新為 processing','2026-06-01 00:37:02'),(27,'111','更新問題回報','問題 #3 狀態更新為 open','2026-06-01 00:37:07'),(28,'111','更新問題回報','問題 #2 狀態更新為 resolved','2026-06-01 00:37:09'),(29,'111','更新問題回報','問題 #2 狀態更新為 open','2026-06-01 00:37:13'),(30,'111','更新問題回報','問題 #3 狀態更新為 resolved','2026-06-01 00:37:16'),(31,'111','更新問題回報','問題 #3 狀態更新為 open','2026-06-01 00:37:20'),(32,'111','更新問題回報','問題 #3 狀態更新為 resolved','2026-06-01 00:37:24'),(33,'111','更新問題回報','問題 #1 狀態更新為 open','2026-06-01 00:37:25'),(34,'111','更新問題回報','問題 #3 狀態更新為 processing','2026-06-01 00:37:27'),(35,'111','更新問題回報','問題 #1 狀態更新為 resolved','2026-06-01 00:37:29'),(36,'111','更新問題回報','問題 #3 狀態更新為 open','2026-06-01 00:40:35'),(37,'111','更新問題回報','問題 #6 狀態更新為 processing','2026-06-01 00:42:39'),(38,'111','更新問題回報','問題 #5 狀態更新為 resolved','2026-06-01 00:42:51'),(39,'111','更新問題回報','問題 #4 狀態更新為 processing','2026-06-01 00:43:00'),(40,'111','更新問題回報','問題 #3 狀態更新為 resolved','2026-06-01 00:43:04'),(41,'111','更新問題回報','問題 #2 狀態更新為 processing','2026-06-01 00:43:05'),(42,'111','更新問題回報','問題 #6 狀態更新為 resolved','2026-06-01 00:43:07'),(43,'222','更新問題回報','問題 #9 狀態更新為 processing','2026-06-01 00:45:07'),(44,'222','更新問題回報','問題 #8 狀態更新為 resolved','2026-06-01 00:45:08'),(45,'222','更新問題回報','問題 #7 狀態更新為 processing','2026-06-01 00:45:09'),(46,'222','更新問題回報','問題 #9 狀態更新為 open','2026-06-01 00:45:10'),(47,'222','更新問題回報','問題 #9 狀態更新為 processing','2026-06-01 00:45:12'),(48,'222','更新問題回報','問題 #7 狀態更新為 open','2026-06-01 00:50:56'),(49,'222','更新問題回報','問題 #7 狀態更新為 processing','2026-06-01 01:00:15'),(50,'222','更新問題回報','問題 #8 狀態更新為 open','2026-06-01 01:00:24'),(51,'222','更新問題回報','問題 #8 狀態更新為 processing','2026-06-01 01:00:27'),(52,'222','更新問題回報','問題 #9 狀態更新為 open','2026-06-01 01:00:32'),(53,'222','更新問題回報','問題 #9 狀態更新為 processing','2026-06-01 01:05:30'),(54,'222','更新問題回報','問題 #8 狀態更新為 open','2026-06-01 01:05:34'),(55,'222','更新問題回報','問題 #7 狀態更新為 open','2026-06-01 01:05:43'),(56,'222','更新問題回報','問題 #8 狀態更新為 resolved','2026-06-01 01:05:47'),(57,'222','更新問題回報','問題 #9 狀態更新為 resolved','2026-06-01 01:07:10'),(58,'222','建立備份工作','建立備份工作 #9: backup_job_20260601_040411，已產生 ZIP 備份','2026-06-01 10:04:13'),(59,'System Admin','修改預算','將 資訊工程學系 預算更新為 $500,000,000','2026-06-01 10:13:20'),(60,'System Admin','修改預算','將 資訊工程學系 預算更新為 $50,000','2026-06-01 10:13:26'),(61,'a1125544','更新問題回報','問題 #10 狀態更新為 processing','2026-06-01 12:54:43'),(62,'111','更新問題回報','問題 #11 狀態更新為 processing','2026-06-01 12:57:48'),(63,'111','更新問題回報','問題 #10 狀態更新為 open','2026-06-01 12:57:59'),(64,'111','更新問題回報','問題 #10 狀態更新為 processing','2026-06-01 12:58:02'),(65,'a1125544','更新問題回報','問題 #12 狀態更新為 processing','2026-06-01 20:29:42'),(66,'a1125544','更新問題回報','問題 #10 狀態更新為 resolved','2026-06-01 20:29:46'),(67,'222','更新問題回報','問題 #13 狀態更新為 processing','2026-06-01 20:38:14'),(68,'222','更新問題回報','問題 #13 狀態更新為 open','2026-06-01 20:38:17'),(69,'222','更新問題回報','問題 #14 狀態更新為 processing','2026-06-01 20:47:29'),(70,'222','更新問題回報','問題 #14 狀態更新為 open','2026-06-01 20:47:31'),(71,'222','建立備份工作','建立備份工作 #10: backup_job_20260601_152904，已產生 ZIP 備份','2026-06-01 21:29:06'),(72,'222','建立資料封存','建立封存 #9: issue_reports_resolved，來源 issue_reports，共 7 筆，檔案 archives/issue_reports_resolved_20260601_152923.csv','2026-06-01 21:29:23'),(73,'222','下載資料封存檔','下載封存 #9: issue_reports_resolved，檔名 issue_reports_resolved.csv','2026-06-01 21:29:27'),(74,'222','上傳還原 SQL','上傳還原 SQL #2: 001_wang_issue_backup.sql，檔案 restore_uploads/restore_sql_20260601_152940_6953487d_001_wang_issue_backup.sql','2026-06-01 21:29:40'),(75,'222','上傳還原 SQL','上傳還原 SQL #3: 001_wang_issue_backup.sql，檔案 restore_uploads/restore_sql_20260601_152957_a865672e_001_wang_issue_backup.sql','2026-06-01 21:29:57');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_notifications`
--

DROP TABLE IF EXISTS `teacher_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_application_id` int DEFAULT NULL,
  `related_issue_report_id` int DEFAULT NULL,
  `dedup_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `email_sent_at` datetime DEFAULT NULL,
  `email_last_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_teacher_notification_dedup` (`dedup_key`),
  KEY `idx_teacher_notifications_user_read` (`teacher_username`,`is_read`,`created_at`),
  KEY `idx_teacher_notifications_application` (`related_application_id`),
  KEY `idx_teacher_notifications_issue_report` (`related_issue_report_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_notifications`
--

LOCK TABLES `teacher_notifications` WRITE;
/*!40000 ALTER TABLE `teacher_notifications` DISABLE KEYS */;
INSERT INTO `teacher_notifications` VALUES (1,'222','issue_report_update','問題回報狀態更新','你提出的問題「asd」狀態已更新為：處理中',NULL,14,'issue-report-14-processing',0,NULL,NULL,'2026-06-01 20:47:29'),(2,'222','issue_report_update','問題回報狀態更新','你提出的問題「asd」狀態已更新為：待處理',NULL,14,'issue-report-14-open',0,NULL,NULL,'2026-06-01 20:47:31');
/*!40000 ALTER TABLE `teacher_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '科系',
  `position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '職位',
  PRIMARY KEY (`username`),
  UNIQUE KEY `id` (`id`),
  CONSTRAINT `teachers_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='老師詳細資料';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (21,'222','西洋語文學系',NULL),(1,'a1125525','資訊工程學系',NULL),(20,'A11255255','資訊管理學系',NULL),(22,'teacher-preview','工藝與創意設計學系',NULL);
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '種類: 學生/老師/系管/獎助單位',
  `real_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '姓名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密碼雜湊或舊明文密碼',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手機',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'email',
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('111','學生','學生測試帳號','123','0900000000','a1125532@mail.nuk.edu.tw',NULL),('222','老師','老師測試帳號','123','0900000000','a1125532@mail.nuk.edu.tw',NULL),('333','系統管理員','系統管理員測試帳號','123','0900000000','a1125532@mail.nuk.edu.tw',NULL),('444','獎助單位','政府','123','0900000000','a1125532@mail.nuk.edu.tw',NULL),('a1125500','系統管理員','獎懲勳','1234','0900000002','a1125500@gmail.com',NULL),('a1125501','系統管理員','a1125501','1234','0900000000','a1125501@gmail.com',NULL),('a1125525','老師','蟹從峰','1234','0900000001','a1125525@gmail.com',NULL),('A11255255','老師','薛從峰','1234','0952095209','A11255255@G',NULL),('a1125531','學生','黃呵呵','1234','0908399535','a1125532@mail.nuk.edu.tw',NULL),('a1125532','學生','吳茹婷','ting2005','0908399535','a1125532@mail.nuk.edu.tw',NULL),('a1125544','學生','胡詠瀚','1234','0900000000','a1125544@gmail.com.tw','uploads/avatars/a1125544_1766320941.png'),('a11255444','學生','古永漢','1234','0900000000','a11255444@G',NULL),('a112554444','學生','朱永漢','1234','0900000000','a112554444@G',NULL),('admin','系統管理員','系統管理員','1234',NULL,'admin@example.com',NULL),('alumni_association','獎助單位','校友總會','1234',NULL,NULL,NULL),('cs_dept','獎助單位','資工系','1234',NULL,NULL,NULL),('intl_office','獎助單位','國際處','1234',NULL,NULL,NULL),('rd_office','獎助單位','研發處','1234',NULL,NULL,NULL),('reviewer-preview','獎助單位','獎助單位端預覽','rrr','','',NULL),('sa_office','獎助單位','生活輔導組','1234',NULL,NULL,NULL),('student-preview','學生','學生端預覽','sss','','',NULL),('teacher-preview','老師','老師端預覽','ttt','','',NULL),('test','獎助單位','test','1234','0900000000','test@gmail.com',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'se_finalproject'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-21  3:01:10
