-- 胡詠瀚：導師奇偶學生名單、成績圖表、推薦信範本、退回補件與截止提醒

CREATE TABLE IF NOT EXISTS mentor_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_username VARCHAR(50) NOT NULL,
    department VARCHAR(100) NOT NULL,
    parity_rule ENUM('odd', 'even', 'all') NOT NULL DEFAULT 'all',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_mentor_assignment (teacher_username, department),
    INDEX idx_mentor_department_rule (department, parity_rule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recommendation_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    category VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO recommendation_templates (template_key, title, category, content)
VALUES
('general', '一般推薦信範本', '一般', '敬啟者：\n本人為 {student_name}（{student_username}）之導師。該生就讀 {department}，申請 {scholarship_name}。依平時觀察，該生學習態度穩定，具備良好責任感。最近平均成績為 {avg_score}，GPA 為 {gpa}，班排百分比為 {rank_percent}。本人推薦該生申請本獎助學金。\n導師：{teacher_name}'),
('financial_need', '清寒學生推薦信範本', '清寒', '敬啟者：\n本人推薦 {student_name} 申請 {scholarship_name}。該生平時努力向學，雖面臨經濟壓力，仍能維持良好學習態度。最近平均成績為 {avg_score}，班排百分比為 {rank_percent}。懇請審查單位給予支持。\n導師：{teacher_name}'),
('academic', '學術績優推薦信範本', '學術', '敬啟者：\n{student_name} 於 {department} 表現優良，申請 {scholarship_name}。其最近 GPA 為 {gpa}，平均成績 {avg_score}，具備持續精進與學術發展潛力，故本人予以推薦。\n導師：{teacher_name}')
ON DUPLICATE KEY UPDATE title = VALUES(title), category = VALUES(category), content = VALUES(content), is_active = 1;

CREATE TABLE IF NOT EXISTS mentor_return_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    teacher_username VARCHAR(50) NOT NULL,
    student_username VARCHAR(50) NOT NULL,
    reason TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_return_application (application_id),
    INDEX idx_return_teacher (teacher_username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_username VARCHAR(50) NOT NULL,
    type ENUM('deadline', 'return', 'recommendation', 'system') NOT NULL DEFAULT 'system',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    unique_key VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    UNIQUE KEY uniq_teacher_notification (unique_key),
    INDEX idx_teacher_notification (teacher_username, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_scholarship_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scholarship_id INT NOT NULL UNIQUE,
    department_filter TEXT NULL,
    min_avg_score DECIMAL(5,2) NULL,
    max_rank_percent DECIMAL(5,4) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
