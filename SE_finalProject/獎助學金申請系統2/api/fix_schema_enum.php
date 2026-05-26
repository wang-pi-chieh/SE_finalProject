<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Change status column from ENUM to VARCHAR to support integers '1', '2', '3'
// We preserve the default '0' (which we map to pending)
// Note: We need to handle current ENUM values. converting ENUM to VARCHAR usually preserves the string values.

$sql = "ALTER TABLE applications MODIFY status VARCHAR(50) DEFAULT '0'";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'message' => 'Schema successfully altered to VARCHAR']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error altering schema: ' . $conn->error]);
}

$conn->close();
?>