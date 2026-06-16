<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'log_utils.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action']) || !isset($data['details'])) {
    echo json_encode(['success' => false, 'message' => 'Missing action or details']);
    exit;
}

$action = $data['action'];
$details = $data['details'];
$operator = $data['operator'] ?? 'System Admin'; // Default if not provided

logAction($operator, $action, $details);

echo json_encode(['success' => true]);
$conn->close();
?>