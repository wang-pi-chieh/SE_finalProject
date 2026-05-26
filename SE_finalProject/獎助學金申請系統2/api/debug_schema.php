<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = [];

// 1. Get Table Schema
$sql = "DESCRIBE applications";
$result = $conn->query($sql);
if ($result) {
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $response['schema'] = $rows;
} else {
    $response['schema_error'] = $conn->error;
}

// 2. Get Sample Data
$sqlData = "SELECT id, status FROM applications LIMIT 10";
$resData = $conn->query($sqlData);
if ($resData) {
    $rows = [];
    while ($row = $resData->fetch_assoc()) {
        $rows[] = $row;
    }
    $response['data'] = $rows;
} else {
    $response['data_error'] = $conn->error;
}

echo json_encode($response);
$conn->close();
?>