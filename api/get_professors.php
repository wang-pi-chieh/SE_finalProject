<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 針對 users 資料表進行查詢，篩選 role 為 '老師'
// Updated to join teachers table
if ($query === '') {
    $sql = "SELECT t.username as id, u.real_name as name, t.department 
                FROM teachers t 
                JOIN users u ON t.username = u.username 
                LIMIT 50";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT t.username as id, u.real_name as name, t.department 
                FROM teachers t 
                JOIN users u ON t.username = u.username 
                WHERE u.real_name LIKE ? 
                LIMIT 20";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%{$query}%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
}

$professors = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Handle null department just in case
        if (empty($row['department'])) {
            $row['department'] = '';
        }
        $professors[] = $row;
    }
} else {
    $professors = [];
}

echo json_encode($professors);
$conn->close();
?>