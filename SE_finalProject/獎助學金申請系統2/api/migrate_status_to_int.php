<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if db_connect.php works
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed']));
}

$conn->begin_transaction();

try {
    // 1. Update 'approved' -> '1'
    $conn->query("UPDATE applications SET status = '1' WHERE status = 'approved'");
    $rows_approved = $conn->affected_rows;

    // 2. Update 'rejected' -> '2'
    $conn->query("UPDATE applications SET status = '2' WHERE status = 'rejected'");
    $rows_rejected = $conn->affected_rows;

    // 3. Update 'needs_action' -> '3'
    $conn->query("UPDATE applications SET status = '3' WHERE status = 'needs_action' OR status = '需補件'");
    $rows_needs_action = $conn->affected_rows;

    // 4. Update 'reviewing' -> '4' (Draft/Reviewing)
    $conn->query("UPDATE applications SET status = '4' WHERE status = 'reviewing' OR status = '審核中'");
    $rows_reviewing = $conn->affected_rows;

    // 5. Update 'pending' -> '0' (Optional, or leave as default?) 
    // Let's map 'pending' to '0' to be consistent with integers
    $conn->query("UPDATE applications SET status = '0' WHERE status = 'pending' OR status = '待審核'");
    $rows_pending = $conn->affected_rows;

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Migration completed successfully',
        'details' => [
            'approved_to_1' => $rows_approved,
            'rejected_to_2' => $rows_rejected,
            'needs_action_to_3' => $rows_needs_action,
            'reviewing_to_4' => $rows_reviewing,
            'pending_to_0' => $rows_pending
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()]);
}

$conn->close();
?>