<?php
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once 'db_connect.php';

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Get the provider_username from the request
$provider_username = isset($_GET['provider_username']) ? $_GET['provider_username'] : '';

if (empty($provider_username)) {
    echo json_encode(['success' => false, 'message' => 'Missing provider_username']);
    exit;
}

try {
    // 1. Get User Role & Real Name first
    $role_sql = "SELECT role, real_name FROM users WHERE username = ?";
    $stmt_role = $conn->prepare($role_sql);
    $stmt_role->bind_param("s", $provider_username);
    $stmt_role->execute();
    $res_role = $stmt_role->get_result();

    if ($res_role->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user_data = $res_role->fetch_assoc();
    $role = $user_data['role'];
    $real_name = $user_data['real_name'];

    // 2. Build Query
    $sql = "SELECT 
                a.id as application_id,
                a.student_username,
                a.application_date,
                a.status,
                s.name as scholarship_name,
                u.real_name as student_name,
                st.department
            FROM applications a
            JOIN scholarships s ON a.scholarship_id = s.id
            JOIN users u ON a.student_username = u.username
            LEFT JOIN students st ON a.student_username = st.username
            LEFT JOIN reference_letters rl ON a.id = rl.application_id
            WHERE (a.recommendation_required = 0 OR (a.recommendation_required = 1 AND rl.status = 1))";

    $types = "";
    $params = [];

    // Filter Logic
    if ($role === 'scholarship_unit' || $role === '獎助單位') {
        $sql .= " AND s.provider_username = ?";
        $types .= "s";
        $params[] = $provider_username;
    } elseif ($role === 'teacher' || $role === '老師') {
        // Teachers only see applications where they are the recommender
        $sql .= " AND a.referrer_name = ?";
        $types .= "s";
        $params[] = $real_name;
    }
    // Admins see all, so no extra AND clause

    $sql .= " ORDER BY a.application_date DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $applications = [];
    while ($row = $result->fetch_assoc()) {
        // Status is now ENUM (string), directly use it or map if needed
        // 'pending', 'reviewing', 'approved', 'rejected', 'needs_action'
        $applications[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $applications]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>