<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

$result = $conn->query("SELECT template_key, title, category FROM recommendation_templates WHERE is_active = 1 ORDER BY category, title");
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
$conn->close();
