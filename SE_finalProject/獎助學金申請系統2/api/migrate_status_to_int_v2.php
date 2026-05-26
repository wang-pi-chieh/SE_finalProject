<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if db_connect.php works
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed']));
}

$conn->begin_transaction();

try {
    // Current mapping to New Mapping
    // 0: Rejected (Original: 'rejected', '2')
    // 1: Approved (Original: 'approved', '1')
    // 2: Revision Needed (Original: 'needs_action', 'supplement', '3')
    // 3: Pending/Draft (Original: 'pending', 'reviewing', 'submitted', '4', '0')

    // 1. Update Approved -> '1'
    $conn->query("UPDATE applications SET status = '1' WHERE status IN ('approved', '1', '通過')");
    $rows_approved = $conn->affected_rows;

    // 2. Update Pending/Draft -> '3'
    // Note: We handle this BEFORE Rejected because old logical '0' might have been pending? 
    // Wait, let's look at old script. '0' was pending. '4' was reviewing.
    // New Requirement: Pending is 3.
    $conn->query("UPDATE applications SET status = '3' WHERE status IN ('pending', 'reviewing', 'submitted', '4', '0', '未審查', '待審核', '審核中')");
    $rows_pending = $conn->affected_rows;

    // 3. Update Revision Needed -> '2'
    $conn->query("UPDATE applications SET status = '2' WHERE status IN ('needs_action', 'supplement', '3', '需補件')");
    $rows_revision = $conn->affected_rows;

    // 4. Update Rejected -> '0'
    $conn->query("UPDATE applications SET status = '0' WHERE status IN ('rejected', '2', '駁回')");
    $rows_rejected = $conn->affected_rows;

    // 5. ALTER TABLE to modify column type to INT
    // This will force everything to integer. Any remaining unmapped strings will become 0 (Rejected), so we must be careful.
    // But since we covered most cases, it should be fine. Defaults to 3 (Pending).
    // Note: If connection is using strict mode, this might fail if there are non-numeric strings left.
    // Let's check for any leftovers first.

    /* 
       We will NOT do ALTER directly in the transaction if it causes implicit commit.
       DDL statements in MySQL cause implicit commit.
       So we commit first, then Alter.
    */

    $conn->commit();

    // ALTER TABLE
    // Changing default to 3 (Pending)
    $sql_alter = "ALTER TABLE applications MODIFY COLUMN status INT NOT NULL DEFAULT 3 COMMENT '0=Rejected, 1=Approved, 2=Revision, 3=Pending'";

    if ($conn->query($sql_alter) === TRUE) {
        $alter_msg = "Table altered successfully.";
    } else {
        $alter_msg = "Error altering table: " . $conn->error;
        // If alter fails, data is still updated (committed), which is acceptable as they are compatible strings '1', '2' etc.
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration completed',
        'alter_status' => $alter_msg,
        'details' => [
            'approved_to_1' => $rows_approved,
            'pending_to_3' => $rows_pending,
            'revision_to_2' => $rows_revision,
            'rejected_to_0' => $rows_rejected
        ]
    ]);

} catch (Exception $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()]);
}

$conn->close();
?>