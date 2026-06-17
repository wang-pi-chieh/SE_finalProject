<?php
// api/db_connect.php
// Central database connection used by API endpoints.

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$password = '';
$dbname = 'scholarshipdata';

$conn = mysqli_init();
if (!$conn) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "mysqli initialization failed"]));
}

$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!$conn->real_connect($host, $user, $password, $dbname, $port)) {
    http_response_code(500);
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

if (!$conn->set_charset("utf8mb4")) {
    http_response_code(500);
    die(json_encode([
        "success" => false,
        "message" => "Failed to set database charset: " . $conn->error
    ]));
}
?>
