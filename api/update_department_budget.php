<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['department']) || !isset($data['budget'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$department = $data['department'];
$budget = (int) $data['budget'];

try {
    // Determine if we are updating by ID or Name. The current structure uses Name.
    // Let's use Name for consistency with get_department_budgets.php

    $stmt = $conn->prepare("UPDATE departments SET budget = ? WHERE name = ?");
    $stmt->bind_param("is", $budget, $department);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            require_once 'log_utils.php';
            $user = isset($data['user']) ? $data['user'] : 'System Admin'; // Get user from request
            logAction($user, '修改預算', "將 $department 預算更新為 $" . number_format($budget));

            echo json_encode(['success' => true, 'message' => 'Budget updated']);
        } else {
            // If affected_rows is 0, it might mean the budget was already that value, or department not found.
            // Let's check if department exists.
            $check = $conn->prepare("SELECT id FROM departments WHERE name = ?");
            $check->bind_param("s", $department);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Budget updated (Same value)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Department not found']);
            }
        }
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>