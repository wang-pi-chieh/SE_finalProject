-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2026-05-27 14:06:07
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `database5`
--

-- --------------------------------------------------------

--
-- 資料表結構 `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL COMMENT '申請編號',
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
  `status` int(11) NOT NULL DEFAULT 3 COMMENT '0=Rejected, 1=Approved, 2=Revision, 3=Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `applications`
--

INSERT INTO `applications` (`id`, `student_username`, `scholarship_id`, `application_date`, `academic_year`, `semester`, `phone`, `email`, `bank_account`, `recommendation_required`, `biography`, `family_housing_status`, `personal_housing_status`, `has_student_loan`, `tuition_waiver`, `previous_scholarship_name`, `proof_documents`, `application_documents`, `family_situation_desc`, `family_members_desc`, `referrer_name`, `referrer_username`, `referrer_relationship`, `review_comment`, `reviewed_at`, `reviewed_by`, `created_at`, `updated_at`, `status`) VALUES
(24, 'a1125544', 1, '2025-12-25', '112', '下', NULL, NULL, NULL, 0, '', '自有', '校內宿舍', '0', '無', '', NULL, '', '123', '123', '', NULL, '', '1', '2025-12-25 15:32:35', 'alumni_association', '2025-12-25 13:04:11', '2025-12-25 15:32:35', 1),
(25, 'a1125544', 1, '2025-12-25', '112', '下', NULL, NULL, NULL, 1, '', '自有', '住家', '0', '身心障礙', '胡詠瀚', NULL, '', '223', '223', '蟹從峰', 'a1125525', '指導教授', '', '2025-12-25 14:10:50', 'alumni_association', '2025-12-25 13:04:47', '2025-12-25 14:10:50', 1),
(26, 'a1125544', 1, '2025-12-26', '113', '1', '0900000000', 'a1125544@gmail.com.tw', 'sc', 0, '[\"..\\/uploads\\/bio_a1125544_1767082264_0.pdf\"]', '自有', '住家', '0', '中低收入戶', '', NULL, '[\"..\\/uploads\\/other_a1125544_1767082264_0.pdf\"]', 'jkh', 'jknk', '', NULL, '', 'check', '2025-12-29 12:20:51', 'alumni_association', '2025-12-25 17:13:34', '2025-12-30 08:11:04', 3),
(27, 'a1125532', 1, '2025-12-30', '113', '1', '0908399535', 'a1125532@mail.nuk.edu.tw', 'sc', 1, '[\"..\\/uploads\\/bio_a1125532_1767081010_0.pdf\"]', '自有', '住家', '0', '低收入戶', '', NULL, '', 'es', 'sfes', '蟹從峰', 'a1125525', '1565', '', '2025-12-30 08:21:38', 'alumni_association', '2025-12-30 07:50:10', '2025-12-30 08:21:38', 2),
(28, 'a1125532', 2, '2025-12-30', '113', '1', '0908399535', 'a1125532@mail.nuk.edu.tw', 'sce', 0, '[\"..\\/uploads\\/bio_a1125532_1767081196_0.pdf\"]', '自有', '校內宿舍', '0', '無', '', NULL, '', 'zcds', 'zv', '', NULL, '', 'pdf', '2025-12-30 07:54:32', 'cs_dept', '2025-12-30 07:50:45', '2025-12-30 07:54:32', 2),
(30, 'a1125531', 1, '2025-12-30', '113', '1', '0908399535', 'a1125532@mail.nuk.edu.tw', 'zc', 0, '[\"..\\/uploads\\/bio_a1125531_1767082780_0.pdf\"]', '租賃', '住家', '0', '無', '', NULL, '', 'ZS', 'szcz', '', NULL, '', NULL, NULL, 'alumni_association', '2025-12-30 08:19:40', '2025-12-30 08:19:40', 3),
(31, 'a1125532', 3, '2025-12-30', '113', '1', '0908399535', 'a1125532@mail.nuk.edu.tw', 'sd', 1, '[\"..\\/uploads\\/bio_a1125532_1767087147_0.pdf\"]', '自有', '住家', '0', '身心障礙', '', NULL, '', 'sc', 'szc', '蟹從峰', 'a1125525', '專題指導教授', NULL, NULL, 'rd_office', '2025-12-30 09:32:27', '2025-12-30 09:32:27', 3);

-- --------------------------------------------------------

--
-- 資料表結構 `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `budget` decimal(15,0) NOT NULL DEFAULT 1000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `departments`
--

INSERT INTO `departments` (`id`, `name`, `category`, `budget`) VALUES
(1, '電機工程學系', '工學院', 1000000),
(2, '土木與環境工程學系', '工學院', 1000000),
(3, '化學工程及材料工程學系', '工學院', 1000000),
(4, '資訊工程學系', '工學院', 50000),
(5, '應用數學系', '理學院', 1000000),
(6, '生命科學系', '理學院', 1000000),
(7, '應用化學系', '理學院', 1000000),
(8, '應用物理學系', '理學院', 1000000),
(9, '應用經濟學系', '管理學院', 1000000),
(10, '亞太工商管理學系', '管理學院', 10000),
(11, '財務金融學系', '管理學院', 1000000),
(12, '資訊管理學系', '管理學院', 1000000),
(13, '法律學系', '法學院', 1000000),
(14, '政治法律學系', '法學院', 1000000),
(15, '財經法律學系', '法學院', 1000000),
(16, '西洋語文學系', '人文社會科學院', 1000000),
(17, '運動健康與休閒學系', '人文社會科學院', 1000000),
(18, '工藝與創意設計學系', '人文社會科學院', 1000000),
(19, '東亞語文學系', '人文社會科學院', 1000000),
(20, '建築學系', '人文社會科學院', 1000000);

-- --------------------------------------------------------

--
-- 資料表結構 `grades`
--

CREATE TABLE `grades` (
  `student_username` varchar(50) NOT NULL COMMENT '學生帳號',
  `academic_year` varchar(10) NOT NULL COMMENT '學年',
  `semester` varchar(10) NOT NULL COMMENT '學期',
  `avg_score` decimal(5,2) DEFAULT NULL COMMENT '平均成績',
  `gpa` decimal(4,2) DEFAULT NULL COMMENT 'GPA',
  `class_rank` int(11) DEFAULT NULL COMMENT '班排',
  `class_size` int(11) DEFAULT NULL COMMENT '全班人數'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `grades`
--

INSERT INTO `grades` (`student_username`, `academic_year`, `semester`, `avg_score`, `gpa`, `class_rank`, `class_size`) VALUES
('a1125544', '111', '上', 90.10, 4.00, 1, 45),
('a1125544', '111', '下', 86.20, 3.85, 5, 45),
('a1125544', '112', '上', 88.50, 3.92, 3, 45);

-- --------------------------------------------------------

--
-- 資料表結構 `homepage_announcements`
--

CREATE TABLE `homepage_announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT '公告標題',
  `content` text DEFAULT NULL COMMENT '公告內容',
  `display_date` date DEFAULT NULL COMMENT '顯示日期',
  `status_label` varchar(50) DEFAULT NULL COMMENT '狀態標籤文字 (例如：進行中、公告)',
  `status_type` varchar(20) DEFAULT NULL COMMENT '狀態樣式：active/notice/warning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `homepage_announcements`
--

INSERT INTO `homepage_announcements` (`id`, `title`, `content`, `display_date`, `status_label`, `status_type`, `created_at`) VALUES
(1, '114學年度上學期獎學金申請開跑', '本學期各項獎學金申請作業即日起開放受理，符合資格之同學請於 10/30 前完成線上申請。', '2025-10-01', '進行中', 'active', '2026-05-27 11:14:12'),
(2, '傑出學術研究獎獲獎名單公告', '恭喜 50 位獲獎同學，完整獲獎名單請至學生事務處生活輔導組查看。', '2025-01-13', '快訊', 'notice', '2026-05-27 11:14:12'),
(3, '系統維護通知', '本系統將於週日凌晨 00:00 至 04:00 進行例行性維護，請避免於該時段操作。', '2025-02-01', '公告', 'warning', '2026-05-27 11:14:12');

-- --------------------------------------------------------

--
-- 資料表結構 `reference_letters`
--

CREATE TABLE `reference_letters` (
  `id` int(11) NOT NULL,
  `teacher_username` varchar(50) NOT NULL COMMENT '老師帳號',
  `application_id` int(11) NOT NULL COMMENT '申請編號',
  `content` text DEFAULT NULL COMMENT '推薦內容',
  `file_path` varchar(255) DEFAULT NULL COMMENT '推薦信附件路徑',
  `status` varchar(20) DEFAULT 'submitted' COMMENT '狀態: draft, submitted',
  `filled_at` date NOT NULL DEFAULT curdate() COMMENT '填寫日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `reference_letters`
--

INSERT INTO `reference_letters` (`id`, `teacher_username`, `application_id`, `content`, `file_path`, `status`, `filled_at`) VALUES
(7, 'a1125525', 25, '加油', NULL, '1', '2025-12-29'),
(8, 'a1125525', 27, 'vsvwea', NULL, '1', '2025-12-30');

-- --------------------------------------------------------

--
-- 資料表結構 `review_records`
--

CREATE TABLE `review_records` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL COMMENT '申請編號',
  `review_date` date NOT NULL DEFAULT curdate() COMMENT '審查日期',
  `result` varchar(50) DEFAULT NULL COMMENT '審查結果',
  `note` text DEFAULT NULL COMMENT '備註',
  `admin_username` varchar(50) NOT NULL COMMENT '系統管理員帳號'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `review_records`
--

INSERT INTO `review_records` (`id`, `application_id`, `review_date`, `result`, `note`, `admin_username`) VALUES
(74, 24, '2025-12-25', '1', '1', 'alumni_association'),
(75, 24, '2025-12-25', '2', '1', 'alumni_association'),
(76, 24, '2025-12-25', '0', '1', 'alumni_association'),
(77, 25, '2025-12-25', '1', '', 'alumni_association'),
(78, 24, '2025-12-25', '1', '1', 'alumni_association'),
(79, 24, '2025-12-25', '2', '1', 'alumni_association'),
(80, 24, '2025-12-25', '0', '1', 'alumni_association'),
(81, 24, '2025-12-25', '2', '1', 'alumni_association'),
(82, 25, '2025-12-25', '2', '', 'alumni_association'),
(83, 24, '2025-12-25', '0', '1', 'alumni_association'),
(84, 25, '2025-12-25', '1', '', 'alumni_association'),
(85, 24, '2025-12-25', '2', '1', 'alumni_association'),
(86, 24, '2025-12-25', '1', '1', 'alumni_association'),
(87, 26, '2025-12-26', '2', '', 'alumni_association'),
(88, 28, '2025-12-30', '2', '', 'cs_dept'),
(89, 28, '2025-12-30', '2', 'pdf', 'cs_dept'),
(90, 28, '2025-12-30', '1', 'pdf', 'cs_dept'),
(91, 28, '2025-12-30', '0', 'pdf', 'cs_dept'),
(92, 28, '2025-12-30', '2', 'pdf', 'cs_dept'),
(93, 27, '2025-12-30', '2', '', 'alumni_association');

-- --------------------------------------------------------

--
-- 資料表結構 `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT '獎學金名稱',
  `provider_username` varchar(50) NOT NULL COMMENT '發布單位帳號',
  `description` text DEFAULT NULL COMMENT '獎學金描述/申請資格',
  `amount` varchar(100) DEFAULT NULL COMMENT '獎助金額',
  `quota` int(11) DEFAULT 0 COMMENT '名額',
  `application_start_date` date DEFAULT NULL COMMENT '申請開始日期',
  `application_end_date` date DEFAULT NULL COMMENT '申請截止日期',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否啟用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `scholarships`
--

INSERT INTO `scholarships` (`id`, `name`, `provider_username`, `description`, `amount`, `quota`, `application_start_date`, `application_end_date`, `is_active`, `created_at`) VALUES
(1, '優秀清寒校友獎學金', 'alumni_association', '提供予家境清寒且學業成績優異之學生。', '20000', 10, '2024-02-01', '2026-06-30', 1, '2025-12-20 09:03:31'),
(2, '各系專屬獎學金', 'cs_dept', '限各系學生申請，學業成績需達班排前 10%。', '10000', 5, '2024-02-01', '2026-06-30', 1, '2025-12-20 09:03:31'),
(3, '學術研究績優獎勵', 'rd_office', '獎勵發表頂尖期刊論文之學生。', '30000', 3, '2024-02-01', '2026-06-30', 1, '2025-12-20 09:03:31'),
(4, '海外交換學生獎學金', 'intl_office', '補助赴海外交換學生之機票與生活費。', '50000', 8, '2024-02-01', '2026-06-30', 1, '2025-12-20 09:03:31'),
(5, '弱勢學生生活助學金', 'sa_office', '提供弱勢學生生活津貼，需參與校內服務學習時數 (每週 6 小時)。前一學期成績須達 60 分以上。', '6000', 20, '2024-02-01', '2026-06-30', 1, '2025-12-20 09:03:31'),
(35, '10/27course', 'alumni_association', 'svaevs', '500000', 2, '2025-12-30', '2025-12-30', 1, '2025-12-30 14:09:35');

-- --------------------------------------------------------

--
-- 資料表結構 `scholarship_eligibility_rules`
--

CREATE TABLE `scholarship_eligibility_rules` (
  `scholarship_id` int(11) NOT NULL COMMENT '獎學金編號',
  `min_gpa` decimal(4,2) DEFAULT NULL COMMENT '最低 GPA',
  `min_avg_score` decimal(5,2) DEFAULT NULL COMMENT '最低平均成績',
  `max_class_rank_percent` decimal(5,2) DEFAULT NULL COMMENT '班排百分比上限，例如 10 代表前 10%',
  `allowed_departments` text DEFAULT NULL COMMENT '允許系所 JSON 陣列，NULL 代表不限',
  `provider_department` varchar(100) DEFAULT NULL COMMENT '獎助單位對應系所',
  `notes` varchar(255) DEFAULT NULL COMMENT '規則說明'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='獎學金資格比對規則';

--
-- 傾印資料表的資料 `scholarship_eligibility_rules`
--

INSERT INTO `scholarship_eligibility_rules` (`scholarship_id`, `min_gpa`, `min_avg_score`, `max_class_rank_percent`, `allowed_departments`, `provider_department`, `notes`) VALUES
(1, 3.50, 85.00, NULL, NULL, NULL, '提供予家境清寒且學業成績優異之學生'),
(2, NULL, NULL, 10.00, '[\"資訊工程學系\"]', '資訊工程學系', '限各系學生申請，學業成績需達班排前 10%'),
(3, 3.80, 90.00, NULL, NULL, NULL, '獎勵發表頂尖期刊論文之學生'),
(4, 3.00, 80.00, NULL, NULL, NULL, '補助赴海外交換學生之機票與生活費'),
(5, NULL, 60.00, NULL, NULL, NULL, '弱勢學生生活津貼，前一學期成績須達 60 分以上');

-- --------------------------------------------------------

--
-- 資料表結構 `scholarship_units`
--

CREATE TABLE `scholarship_units` (
  `username` varchar(50) NOT NULL,
  `unit_name` varchar(100) DEFAULT NULL COMMENT '名稱 (單位名稱)',
  `person_in_charge` varchar(100) DEFAULT NULL COMMENT '負責人'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='獎助單位詳細資料';

--
-- 傾印資料表的資料 `scholarship_units`
--

INSERT INTO `scholarship_units` (`username`, `unit_name`, `person_in_charge`) VALUES
('444', '政府', '王哈哈'),
('alumni_association', '國立高雄大學校友總會', NULL),
('cs_dept', '資訊工程系', NULL),
('intl_office', '國際事務處', NULL),
('rd_office', '研發處', NULL),
('sa_office', '生活輔導組', NULL),
('test', NULL, NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `students`
--

CREATE TABLE `students` (
  `username` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL COMMENT '系所',
  `gender` varchar(10) DEFAULT NULL COMMENT '性別',
  `grade_level` varchar(50) DEFAULT NULL COMMENT '系級 (例如: 大三, 碩一)',
  `class_name` varchar(50) DEFAULT NULL COMMENT '班級 (例如: 甲班)',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `application_history` text DEFAULT NULL COMMENT '申請紀錄'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生詳細資料';

--
-- 傾印資料表的資料 `students`
--

INSERT INTO `students` (`username`, `department`, `gender`, `grade_level`, `class_name`, `address`, `application_history`) VALUES
('111', '西洋語文學系', NULL, NULL, NULL, NULL, NULL),
('a1125531', '資訊工程學系', NULL, NULL, NULL, NULL, NULL),
('a1125532', '財經法律學系', '男', '116', '55', '', NULL),
('a1125544', '資訊工程學系', '男', '大三', '資工A', '東勢里14鄰健康路183巷8弄32號', NULL),
('a11255444', '資訊工程學系', NULL, NULL, NULL, NULL, NULL),
('a112554444', '資訊管理學系', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_username` varchar(50) NOT NULL COMMENT '學生帳號',
  `type` varchar(50) NOT NULL COMMENT 'result_approved/result_rejected/result_revision/deadline_reminder/eligibility_recommendation',
  `title` varchar(255) NOT NULL COMMENT '通知標題',
  `message` text NOT NULL COMMENT '通知內容',
  `related_application_id` int(11) DEFAULT NULL COMMENT '關聯申請編號',
  `related_scholarship_id` int(11) DEFAULT NULL COMMENT '關聯獎學金編號',
  `dedup_key` varchar(255) NOT NULL COMMENT '去重用鍵值',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已讀',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生站內通知';

--
-- 傾印資料表的資料 `student_notifications`
--

INSERT INTO `student_notifications` (`id`, `student_username`, `type`, `title`, `message`, `related_application_id`, `related_scholarship_id`, `dedup_key`, `is_read`, `created_at`) VALUES
(6, 'a1125532', 'result_revision', '審查結果：需補件', '您的「各系專屬獎學金」申請需補件：pdf', 28, 2, 'result-revision-28', 1, '2026-05-27 11:34:31'),
(9, 'a1125532', 'result_revision', '審查結果：需補件', '您的「優秀清寒校友獎學金」申請需補件：請檢查缺漏資料', 27, 1, 'result-revision-27', 0, '2026-05-27 11:35:22'),
(11, 'a1125544', 'result_approved', '審查結果：已通過', '恭喜您！您申請的「優秀清寒校友獎學金」已通過審核，預計於下個月撥款。', 24, 1, 'result-approved-24', 0, '2026-05-27 11:38:10'),
(12, 'a1125544', 'result_approved', '審查結果：已通過', '恭喜您！您申請的「優秀清寒校友獎學金」已通過審核，預計於下個月撥款。', 25, 1, 'result-approved-25', 0, '2026-05-27 11:38:10'),
(13, 'a1125544', 'eligibility_recommendation', '為您推薦獎學金', '依您的系所與成績，建議申請「各系專屬獎學金」。系所符合：資訊工程學系；班排 3/45，符合前 10%；限各系學生申請，學業成績需達班排前 10%', NULL, 2, 'recommendation-2', 0, '2026-05-27 11:38:10'),
(14, 'a1125544', 'eligibility_recommendation', '為您推薦獎學金', '依您的系所與成績，建議申請「海外交換學生獎學金」。GPA 3.92 達標；平均成績 88.5 達標；補助赴海外交換學生之機票與生活費', NULL, 4, 'recommendation-4', 0, '2026-05-27 11:38:10'),
(15, 'a1125544', 'eligibility_recommendation', '為您推薦獎學金', '依您的系所與成績，建議申請「弱勢學生生活助學金」。平均成績 88.5 達標；弱勢學生生活津貼，前一學期成績須達 60 分以上', NULL, 5, 'recommendation-5', 0, '2026-05-27 11:38:10');

-- --------------------------------------------------------

--
-- 資料表結構 `system_admins`
--

CREATE TABLE `system_admins` (
  `username` varchar(50) NOT NULL,
  `office` varchar(100) DEFAULT NULL COMMENT '處室'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統管理員詳細資料';

--
-- 傾印資料表的資料 `system_admins`
--

INSERT INTO `system_admins` (`username`, `office`) VALUES
('333', NULL),
('a1125500', '教務處'),
('a1125501', NULL),
('admin', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_role`, `action_type`, `details`, `created_at`) VALUES
(1, 'System Tester', '測試紀錄', '这是一条测试日志', '2025-12-26 23:35:49'),
(2, 'System Admin', '新增使用者', '新增帳號: 12345123 (學生), 姓名: 12345123', '2025-12-26 23:43:09'),
(3, '123', '刪除使用者', '刪除帳號: 12345123', '2025-12-26 23:47:51'),
(4, 'System Admin', '修改預算', '將 亞太工商管理學系 預算更新為 $10,000', '2025-12-26 23:48:07'),
(5, '123', '新增獎學金', '新增項目: 獎懲勳你好 (金額: $2000)', '2025-12-26 23:48:39'),
(6, '123', '刪除獎學金', '刪除項目ID: 33', '2025-12-26 23:48:53'),
(7, '123', '刪除獎學金', '刪除項目ID: 31', '2025-12-26 23:48:56'),
(8, '123', '系統備份', '下載完整備份: backup_scholarship_system_2025-12-26_16-49-05.zip', '2025-12-26 23:49:09'),
(9, 'System Admin', '匯出報表', '匯出學系獎學金預算與分配概況報表', '2025-12-26 23:55:35'),
(10, 'System Admin', '新增使用者', '新增帳號: 12345123 (學生), 姓名: 12345123', '2025-12-26 23:58:57'),
(11, '123', '刪除使用者', '刪除帳號: 12345123', '2025-12-26 23:59:21'),
(12, 'System Admin', '匯出報表', '匯出學系獎學金預算與分配概況報表', '2025-12-27 00:24:08'),
(13, 'admin', '新增獎學金', '新增項目: i am rich (金額: $100000000)', '2025-12-30 16:26:15'),
(14, 'a1125532', '新增獎學金', '新增項目: 10/27course (金額: $500000)', '2025-12-30 22:09:35');

-- --------------------------------------------------------

--
-- 資料表結構 `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL COMMENT '科系',
  `position` varchar(50) DEFAULT NULL COMMENT '職位'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='老師詳細資料';

--
-- 傾印資料表的資料 `teachers`
--

INSERT INTO `teachers` (`id`, `username`, `department`, `position`) VALUES
(21, '222', '西洋語文學系', NULL),
(1, 'a1125525', '資訊工程學系', NULL),
(20, 'A11255255', '資訊管理學系', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

CREATE TABLE `users` (
  `username` varchar(50) NOT NULL,
  `role` varchar(20) NOT NULL COMMENT '種類: 學生/老師/系管/獎助單位',
  `real_name` varchar(50) NOT NULL COMMENT '姓名',
  `password` varchar(20) NOT NULL COMMENT '密碼',
  `phone` varchar(20) DEFAULT NULL COMMENT '手機',
  `email` varchar(100) DEFAULT NULL COMMENT 'email',
  `avatar_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`username`, `role`, `real_name`, `password`, `phone`, `email`, `avatar_url`) VALUES
('111', '學生', '哇哇哇', '123', '0900000000', 'a1125532@mail.nuk.edu.tw', NULL),
('222', '老師', '哇哇哇', '123', '0900000000', 'a1125532@mail.nuk.edu.tw', NULL),
('333', '系統管理員', '哇哇哇', '123', '0900000000', 'a1125532@mail.nuk.edu.tw', NULL),
('444', '獎助單位', '政府', '123', '0900000000', 'a1125532@mail.nuk.edu.tw', NULL),
('a1125500', '系統管理員', '獎懲勳', '1234', '0900000002', 'a1125500@gmail.com', NULL),
('a1125501', '系統管理員', 'a1125501', '1234', '0900000000', 'a1125501@gmail.com', NULL),
('a1125525', '老師', '蟹從峰', '1234', '0900000001', 'a1125525@gmail.com', NULL),
('A11255255', '老師', '薛從峰', '1234', '0952095209', 'A11255255@G', NULL),
('a1125531', '學生', '黃呵呵', '1234', '0908399535', 'a1125532@mail.nuk.edu.tw', NULL),
('a1125532', '學生', '吳茹婷', 'ting2005', '0908399535', 'a1125532@mail.nuk.edu.tw', NULL),
('a1125544', '學生', '胡詠瀚', '1234', '0900000000', 'a1125544@gmail.com.tw', 'uploads/avatars/a1125544_1766320941.png'),
('a11255444', '學生', '古永漢', '1234', '0900000000', 'a11255444@G', NULL),
('a112554444', '學生', '朱永漢', '1234', '0900000000', 'a112554444@G', NULL),
('admin', '系統管理員', '系統管理員', '1234', NULL, 'admin@example.com', NULL),
('alumni_association', '獎助單位', '校友總會', '1234', NULL, NULL, NULL),
('cs_dept', '獎助單位', '資工系', '1234', NULL, NULL, NULL),
('intl_office', '獎助單位', '國際處', '1234', NULL, NULL, NULL),
('rd_office', '獎助單位', '研發處', '1234', NULL, NULL, NULL),
('sa_office', '獎助單位', '生活輔導組', '1234', NULL, NULL, NULL),
('test', '獎助單位', 'test', '1234', '0900000000', 'test@gmail.com', NULL);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_username` (`student_username`),
  ADD KEY `scholarship_id` (`scholarship_id`),
  ADD KEY `fk_app_referrer_username` (`referrer_username`);

--
-- 資料表索引 `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 資料表索引 `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`student_username`,`academic_year`,`semester`);

--
-- 資料表索引 `homepage_announcements`
--
ALTER TABLE `homepage_announcements`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `reference_letters`
--
ALTER TABLE `reference_letters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_recommendation` (`teacher_username`,`application_id`),
  ADD KEY `application_id` (`application_id`);

--
-- 資料表索引 `review_records`
--
ALTER TABLE `review_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_records_application_fk` (`application_id`),
  ADD KEY `review_records_user_fk` (`admin_username`);

--
-- 資料表索引 `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_username` (`provider_username`);

--
-- 資料表索引 `scholarship_eligibility_rules`
--
ALTER TABLE `scholarship_eligibility_rules`
  ADD PRIMARY KEY (`scholarship_id`);

--
-- 資料表索引 `scholarship_units`
--
ALTER TABLE `scholarship_units`
  ADD PRIMARY KEY (`username`);

--
-- 資料表索引 `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`username`);

--
-- 資料表索引 `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_notification_dedup` (`dedup_key`),
  ADD KEY `idx_student_notifications_user_read` (`student_username`,`is_read`,`created_at`),
  ADD KEY `idx_student_notifications_application` (`related_application_id`),
  ADD KEY `idx_student_notifications_scholarship` (`related_scholarship_id`);

--
-- 資料表索引 `system_admins`
--
ALTER TABLE `system_admins`
  ADD PRIMARY KEY (`username`);

--
-- 資料表索引 `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `id` (`id`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`username`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請編號', AUTO_INCREMENT=32;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `homepage_announcements`
--
ALTER TABLE `homepage_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `reference_letters`
--
ALTER TABLE `reference_letters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `review_records`
--
ALTER TABLE `review_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_app_referrer_username` FOREIGN KEY (`referrer_username`) REFERENCES `users` (`username`) ON DELETE SET NULL;

--
-- 資料表的限制式 `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE;

--
-- 資料表的限制式 `reference_letters`
--
ALTER TABLE `reference_letters`
  ADD CONSTRAINT `reference_letters_ibfk_1` FOREIGN KEY (`teacher_username`) REFERENCES `teachers` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `reference_letters_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `review_records`
--
ALTER TABLE `review_records`
  ADD CONSTRAINT `review_records_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_records_user_fk` FOREIGN KEY (`admin_username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- 資料表的限制式 `scholarships`
--
ALTER TABLE `scholarships`
  ADD CONSTRAINT `scholarships_ibfk_1` FOREIGN KEY (`provider_username`) REFERENCES `scholarship_units` (`username`) ON DELETE CASCADE;

--
-- 資料表的限制式 `scholarship_eligibility_rules`
--
ALTER TABLE `scholarship_eligibility_rules`
  ADD CONSTRAINT `scholarship_eligibility_rules_fk` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `scholarship_units`
--
ALTER TABLE `scholarship_units`
  ADD CONSTRAINT `scholarship_units_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- 資料表的限制式 `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- 資料表的限制式 `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD CONSTRAINT `student_notifications_application_fk` FOREIGN KEY (`related_application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_notifications_scholarship_fk` FOREIGN KEY (`related_scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_notifications_student_fk` FOREIGN KEY (`student_username`) REFERENCES `students` (`username`) ON DELETE CASCADE;

--
-- 資料表的限制式 `system_admins`
--
ALTER TABLE `system_admins`
  ADD CONSTRAINT `system_admins_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- 資料表的限制式 `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_fk` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
