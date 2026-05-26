<?php
// api/get_admin_homepage_announcements.php
header('Content-Type: application/json');
require_once 'db_connect.php';

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
if ($limit <= 0) $limit = 10;
if ($page <= 0) $page = 1;
$offset = ($page - 1) * $limit;

try {
    // 總筆數
    $countSql = "SELECT COUNT(*) AS total FROM homepage_announcements";
    $countResult = $conn->query($countSql);
    $total = 0;
    if ($countResult && $row = $countResult->fetch_assoc()) {
        $total = (int)$row['total'];
    }

    // 當頁資料
    $sql = "SELECT id, title, content, display_date, status_label, status_type, created_at
            FROM homepage_announcements
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $totalPages = $limit > 0 ? ceil($total / $limit) : 1;

    echo json_encode([
        'success' => true,
        'data' => $rows,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'limit' => $limit
        ]
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


