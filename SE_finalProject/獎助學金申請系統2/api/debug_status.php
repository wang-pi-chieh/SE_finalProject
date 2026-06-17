<?php
require 'db_connect.php';

$student_username = 'a1125544';
if (isset($_GET['u']))
    $student_username = $_GET['u'];

echo "<h1>Debug Info</h1>";

function dumpTable($conn, $table)
{
    echo "<h3>Table: $table</h3>";
    $res = $conn->query("DESCRIBE $table");
    if (!$res) {
        echo "Error: " . $conn->error;
        return;
    }
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
}

dumpTable($conn, 'users');
dumpTable($conn, 'applications');

echo "<hr>";

// Try to list applications blindly
$sql = "SELECT * FROM applications WHERE student_username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_username);
$stmt->execute();
$res = $stmt->get_result();

echo "<h3>Applications for $student_username</h3>";
echo "<table border='1'>";
// Header
$first = true;
while ($row = $res->fetch_assoc()) {
    if ($first) {
        echo "<tr>";
        foreach (array_keys($row) as $k)
            echo "<th>$k</th>";
        echo "</tr>";
        $first = false;
    }
    echo "<tr>";
    foreach ($row as $v)
        echo "<td>$v</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();