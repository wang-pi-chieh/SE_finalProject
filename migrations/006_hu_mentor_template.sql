-- 006_hu_mentor_template.sql
-- 新增 mentor_type 欄位 (odd, even, all)，預設為 all
ALTER TABLE `teachers`
ADD COLUMN `mentor_type` ENUM('odd', 'even', 'all') DEFAULT 'all' COMMENT '導師單雙號類型';

-- 測試資料：將老師 a1125525 (蟹從峰) 設為雙數導師
UPDATE `teachers` SET `mentor_type` = 'even' WHERE `username` = 'a1125525';
-- 測試資料：將老師 A11255255 (薛從峰) 設為單數導師
UPDATE `teachers` SET `mentor_type` = 'odd' WHERE `username` = 'A11255255';
