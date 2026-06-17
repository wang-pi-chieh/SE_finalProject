<?php
require 'api/db_connect.php';
$result = $conn->query('SELECT * FROM system_logs');
echo "Count: " . $result->num_rows . "\n";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>