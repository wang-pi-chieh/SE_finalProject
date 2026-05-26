<?php
require 'api/db_connect.php';

$result = $conn->query("SELECT username, role, real_name FROM users");
if ($result) {
    echo "Username | Role | Real Name\n";
    echo "---|---|---\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['username']} | {$row['role']} | {$row['real_name']}\n";
    }
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>