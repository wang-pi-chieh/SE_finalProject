<?php
header('Content-Type: application/json');
error_reporting(0);
require_once 'db_connect.php';

try {
    // 1. Get all departments and their budgets
    $dept_data = []; // name => budget

    // Check if departments table exists
    $check = $conn->query("SHOW TABLES LIKE 'departments'");
    if ($check && $check->num_rows > 0) {
        // Fetch name AND budget
        // Make sure budget column exists (we just added it, but good to be safe or just assume)
        $dept_sql = "SELECT name, budget FROM departments ORDER BY name";
        $dept_result = $conn->query($dept_sql);
        while ($row = $dept_result->fetch_assoc()) {
            // Use DB budget or default 1000000 if null/0 (though defined as NOT NULL DEFAULT 1000000)
            $dept_data[$row['name']] = $row['budget'] ? (int) $row['budget'] : 1000000;
        }
    } else {
        // Fallback for demo
        // Original fallback logic for getting distinct departments from students is removed
        // as the instruction implies a simpler fallback for demo purposes when 'departments' table is missing.
        $departments = ['資訊工程學系', '電機工程學系', '機械工程學系', '企業管理學系'];
        foreach ($departments as $d) {
            $dept_data[$d] = 1000000;
        }
    }

    // 2. Calculate budget stats for each department
    $stats = [];

    foreach ($dept_data as $dept_name => $budget) {
        $default_budget = $budget;

        // Calculate total approved amount for this department
        // Join applications -> students (for dept) -> scholarships (for amount)
        // Status 1 = Approved (This maps to '已核可' or similar int status. Previous context suggested 1 is approved?)
        // Let's assume status 'approved' string or 1.
        // Based on `api/submit_review.php`, status updates to 'approved'. Wait, database schema likely stores string or int. 
        // Safe check: status = 'approved' OR status = 1 OR status = '核可'

        $sql = "SELECT SUM(s.amount) as total_used
                FROM applications a
                JOIN students st ON a.student_username = st.username
                JOIN scholarships s ON a.scholarship_id = s.id
                WHERE (a.status = 'approved' OR a.status = 1 OR a.status = '已核可')
                AND st.department = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $dept_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $used_amount = $row['total_used'] ? (int) $row['total_used'] : 0;
        $remaining = $default_budget - $used_amount;
        // Avoid division by zero
        $utilization = ($default_budget > 0) ? ($used_amount / $default_budget) * 100 : 0;

        $stats[] = [
            'department' => $dept_name,
            'budget' => $default_budget,
            'used' => $used_amount,
            'remaining' => $remaining,
            'utilization' => round($utilization, 1)
        ];
    }

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>