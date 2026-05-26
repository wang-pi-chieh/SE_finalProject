<?php
// api/get_homepage_announcements.php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    // 取最近建立的公告，最多顯示 5 筆，首頁可以再自行裁切
    $sql = "SELECT id, title, content, display_date, status_label, status_type
            FROM homepage_announcements
            ORDER BY created_at DESC
            LIMIT 5";

    $result = $conn->query($sql);

    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $rows
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>


