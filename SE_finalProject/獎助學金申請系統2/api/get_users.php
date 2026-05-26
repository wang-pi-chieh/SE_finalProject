<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : 'all';

try {
    // Get total count
    if ($roleFilter !== 'all' && $roleFilter !== '') {
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE (username LIKE ? OR real_name LIKE ?) AND role = ?");
        $countStmt->bind_param("sss", $search, $search, $roleFilter);
    } else {
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE username LIKE ? OR real_name LIKE ?");
        $countStmt->bind_param("ss", $search, $search);
    }
    $countStmt->execute();
    $totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $limit);

    // Get users
    if ($roleFilter !== 'all' && $roleFilter !== '') {
        $stmt = $conn->prepare("SELECT username, real_name, email, role FROM users WHERE (username LIKE ? OR real_name LIKE ?) AND role = ? ORDER BY username ASC LIMIT ? OFFSET ?");
        $stmt->bind_param("sssii", $search, $search, $roleFilter, $limit, $offset);
    } else {
        $stmt = $conn->prepare("SELECT username, real_name, email, role FROM users WHERE username LIKE ? OR real_name LIKE ? ORDER BY username ASC LIMIT ? OFFSET ?");
        $stmt->bind_param("ssii", $search, $search, $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total_users' => $totalUsers,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>