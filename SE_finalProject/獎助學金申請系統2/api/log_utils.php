<?php
require_once 'db_connect.php';

function logAction($user_role, $action_type, $details)
{
    global $conn;

    // Prepare statement to avoid SQL injection
    $stmt = $conn->prepare("INSERT INTO system_logs (user_role, action_type, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $user_role, $action_type, $details);
        $stmt->execute();
        $stmt->close();
    }
}
?>