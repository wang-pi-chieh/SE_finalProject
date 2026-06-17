<?php
// api/db_connect.php

// 避免 mysqli 在錯誤時直接丟出例外 (mysqli_sql_exception)，改由程式自行檢查 errno
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

if (!function_exists('db_env')) {
    function db_env($keys, $default = '')
    {
        foreach ((array) $keys as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                return $value;
            }
        }
        return $default;
    }
}

$host = db_env(['DB_HOST', 'MYSQL_HOST'], 'localhost');
$user = db_env(['DB_USER', 'MYSQL_USER', 'MYSQL_USERNAME'], 'root');
$password = db_env(['DB_PASSWORD', 'MYSQL_PASSWORD', 'MYSQL_ROOT_PASSWORD'], '');
$dbname = db_env(['DB_NAME', 'MYSQL_DATABASE'], 'se_finalproject');
$port = (int) db_env(['DB_PORT', 'MYSQL_PORT'], '3306');
if ($port <= 0) {
    $port = 3306;
}

$conn = @new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// 設定編碼，防止亂碼
$conn->set_charset("utf8mb4");
?>
