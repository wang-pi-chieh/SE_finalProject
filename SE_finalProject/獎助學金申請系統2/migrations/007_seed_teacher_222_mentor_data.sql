-- Seed data for teacher 222 mentor dashboard demo records.

CREATE TABLE IF NOT EXISTS mentor_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_username VARCHAR(50) NOT NULL,
    department VARCHAR(100) NOT NULL,
    parity_rule ENUM('odd', 'even', 'all') NOT NULL DEFAULT 'all',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_mentor_assignment (teacher_username, department),
    INDEX idx_mentor_department_rule (department, parity_rule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_scholarship_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scholarship_id INT NOT NULL UNIQUE,
    department_filter TEXT NULL,
    min_avg_score DECIMAL(5,2) NULL,
    max_rank_percent DECIMAL(5,4) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM mentor_assignments WHERE teacher_username = '222';

INSERT INTO mentor_assignments (teacher_username, department, parity_rule)
VALUES ('222', '工藝與創意設計學系', 'all');

INSERT INTO grades (student_username, academic_year, semester, avg_score, gpa, class_rank, class_size)
VALUES
    ('111', '111', '1', 88.00, 3.90, 3, 48),
    ('111', '111', '2', 89.50, 4.00, 2, 48)
ON DUPLICATE KEY UPDATE
    avg_score = VALUES(avg_score),
    gpa = VALUES(gpa),
    class_rank = VALUES(class_rank),
    class_size = VALUES(class_size);
