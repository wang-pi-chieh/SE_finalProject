<?php
require 'api/db_connect.php';

$sql = "INSERT INTO users (username, password, role, real_name, email) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$u = 'admin';
$p = 'admin';
$r = '系統管理員';
$n = '系統管理員';
$e = 'admin@example.com';
$stmt->bind_param("sssss", $u, $p, $r, $n, $e);

if ($stmt->execute()) {
    echo "Admin user created successfully.";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>