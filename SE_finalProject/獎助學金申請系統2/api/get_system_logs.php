<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Allow fetching logs, sorted by newest first
$sql = "SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 100"; // Cap at 100 for now or add pagination params
$result = $conn->query($sql);

$logs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $logs]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch logs']);
}

$conn->close();
?>