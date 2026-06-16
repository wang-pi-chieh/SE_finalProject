<?php
require 'api/db_connect.php';
$result = $conn->query('DESCRIBE departments');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Table departments not found or error: " . $conn->error;
}
?>