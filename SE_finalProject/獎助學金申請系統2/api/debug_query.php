<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'db_connect.php';

$provider_username = 'alumni_association'; // Adjust as needed
// Get role for logic
$role_sql = "SELECT role, real_name FROM users WHERE username = ?";
$stmt_role = $conn->prepare($role_sql);
$stmt_role->bind_param("s", $provider_username);
$stmt_role->execute();
$user_data = $stmt_role->get_result()->fetch_assoc();
$role = $user_data['role'];
$real_name = $user_data['real_name'];

echo "Role: $role<br>";

$sql = "SELECT 
            a.id as application_id,
            a.status,
            a.student_username,
            s.name as scholarship_name,
            rl.status as rl_status
        FROM applications a
        JOIN scholarships s ON a.scholarship_id = s.id
        JOIN users u ON a.student_username = u.username
        LEFT JOIN students st ON a.student_username = st.username
        LEFT JOIN reference_letters rl ON a.id = rl.application_id
        WHERE (a.recommendation_required = 0 OR (a.recommendation_required = 1 AND rl.status = 1))";

if ($role === 'scholarship_unit' || $role === '獎助單位') {
    $sql .= " AND s.provider_username = '$provider_username'";
}

$sql .= " ORDER BY a.application_date DESC";

echo "SQL: $sql <br><hr>";

$result = $conn->query($sql);
if (!$result)
    die("Error: " . $conn->error);

echo "<table border=1><tr><th>ID</th><th>App Status (a.status)</th><th>Rec Letter Status (rl.status)</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['application_id']}</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['rl_status']}</td>";
    echo "</tr>";
}
echo "</table>";
?>