<?php
// diagnose_db.php
$host = 'localhost';
$user = 'root';
$password = 'A1125544@';
$dbname = 'scholarshipData';

echo "Testing connection to $host user:$user db:$dbname ...\n";

// Set a short timeout
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_init();
    if (!$conn) {
        die("mysqli_init failed");
    }

    if (!$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
        die("Setting MYSQLI_OPT_CONNECT_TIMEOUT failed");
    }

    if (!@$conn->real_connect($host, $user, $password, $dbname)) {
        throw new Exception($conn->connect_error, $conn->connect_errno);
    }

    echo "SUCCESS: Connection established.\n";
    echo "Server Info: " . $conn->server_info . "\n";
    $conn->close();
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}
?>