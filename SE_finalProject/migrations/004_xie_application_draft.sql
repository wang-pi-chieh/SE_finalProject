-- 謝從峰：學生申請草稿、修改、檔案格式檢查與送出前驗證

CREATE TABLE IF NOT EXISTS application_drafts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_username VARCHAR(50) NOT NULL,
    scholarship_id INT NOT NULL,
    draft_payload JSON NOT NULL,
    status ENUM('draft', 'submitted', 'discarded') NOT NULL DEFAULT 'draft',
    submitted_application_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_scholarship_draft (student_username, scholarship_id),
    INDEX idx_application_draft_status (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS application_validation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_username VARCHAR(50) NOT NULL,
    scholarship_id INT NOT NULL,
    can_submit TINYINT(1) NOT NULL DEFAULT 0,
    message TEXT NOT NULL,
    checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_validation_student (student_username, checked_at),
    INDEX idx_validation_scholarship (scholarship_id, checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
