<?php
require 'api/db_connect.php';
// Add budget column if not exists
$sql = "ALTER TABLE departments ADD COLUMN budget DECIMAL(15,0) NOT NULL DEFAULT 1000000";
if ($conn->query($sql) === TRUE) {
    echo "Column budget added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
$conn->close();
?>