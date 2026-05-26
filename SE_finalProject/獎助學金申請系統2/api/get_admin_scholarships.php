<?php
// api/get_admin_scholarships.php
header('Content-Type: application/json');
require 'db_connect.php';

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 6;
$offset = ($page - 1) * $limit;

// 1. Get Total Count
$countSql = "SELECT COUNT(*) as total FROM scholarships";
$countResult = $conn->query($countSql);
$total = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $total = (int) $row['total'];
}

// 2. Get Data for Current Page
// 同時帶出獎助單位名稱 (unit_name)，方便前端顯示
$sql = "SELECT s.*, su.unit_name 
        FROM scholarships s
        LEFT JOIN scholarship_units su ON s.provider_username = su.username
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$scholarships = [];
while ($row = $result->fetch_assoc()) {
    $scholarships[] = $row;
}

$totalPages = ceil($total / $limit);

echo json_encode([
    "success" => true,
    "data" => $scholarships,
    "pagination" => [
        "current_page" => $page,
        "total_pages" => $totalPages,
        "total_items" => $total,
        "limit" => $limit
    ]
]);

$stmt->close();
$conn->close();
?>