<?php
// 建立首頁公告表的輔助腳本（只需在第一次部署時執行一次）
require_once 'api/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS homepage_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    display_date DATE NULL,
    status_label VARCHAR(50) NULL,
    status_type VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo \"Table homepage_announcements created or already exists.\";
} else {
    echo \"Error creating table: \" . $conn->error;
}

$conn->close();
?>


