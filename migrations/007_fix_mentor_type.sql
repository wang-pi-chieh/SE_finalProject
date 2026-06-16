-- 007_fix_mentor_type.sql
SET NAMES utf8mb4;

-- 先將 mentor_type 轉成字串，以便進行 UPDATE 轉換
ALTER TABLE `teachers` MODIFY COLUMN `mentor_type` VARCHAR(10);

-- 更新現有資料：odd -> 1 (奇數), even -> 0 (偶數), all -> 0 (預設偶數)
UPDATE `teachers` SET `mentor_type` = '1' WHERE `mentor_type` = 'odd';
UPDATE `teachers` SET `mentor_type` = '0' WHERE `mentor_type` = 'even' OR `mentor_type` = 'all';

-- 正式將型別改為 TINYINT，並加上正確的 UTF-8 註解
ALTER TABLE `teachers` MODIFY COLUMN `mentor_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '導師單雙號類型 (0:偶數, 1:奇數)';
