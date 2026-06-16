<?php
// api/db_connect.php

// 避免 mysqli 在錯誤時直接丟出例外 (mysqli_sql_exception)，改由程式自行檢查 errno
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'se_finalproject';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// 設定編碼，防止亂碼
$conn->set_charset("utf8mb4");
?>