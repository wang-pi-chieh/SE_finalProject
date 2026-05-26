<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'api/db_connect.php';

echo "Database connection successful!\n";

$tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    echo "Tables in DB: " . implode(", ", $tables) . "\n";
} else {
    echo "Failed to list tables: " . $conn->error . "\n";
}

// Check if departments table exists
if (in_array('departments', $tables)) {
    echo "Departments table exists.\n";
    $res = $conn->query("SELECT COUNT(*) FROM departments");
    $row = $res->fetch_row();
    echo "Departments count: " . $row[0] . "\n";
} else {
    echo "Departments table does NOT exist.\n";
}

// Check students table
if (in_array('students', $tables)) {
    $res = $conn->query("SELECT DISTINCT department FROM students");
    if ($res) {
        $depts = [];
        while ($row = $res->fetch_assoc()) {
            $depts[] = $row['department'];
        }
        echo "Distinct departments in students table: " . implode(", ", $depts) . "\n";
    }
}

// Check api/get_department_budgets.php logic
echo "\n--- Testing get_department_budgets.php logic ---\n";
// Determine departments list again for the test
$departments = [];
if (in_array('departments', $tables)) {
    $dept_sql = "SELECT name FROM departments ORDER BY name";
    $dept_result = $conn->query($dept_sql);
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row['name'];
    }
} else {
    $dept_sql = "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department";
    $dept_result = $conn->query($dept_sql);
    if ($dept_result) {
        while ($row = $dept_result->fetch_assoc()) {
            $departments[] = $row['department'];
        }
    }
}
if (empty($departments)) {
    // If empty, the API hardcodes them.
    echo "No departments found in DB. API will use hardcoded defaults.\n";
    $departments = ['資訊工程學系', '電機工程學系', '機械工程學系', '企業管理學系'];
}

echo "Departments being processed: " . implode(", ", $departments) . "\n";

foreach ($departments as $dept_name) {
    $sql = "SELECT SUM(s.amount) as total_used
            FROM applications a
            JOIN students st ON a.student_username = st.username
            JOIN scholarships s ON a.scholarship_id = s.id
            WHERE (a.status = 'approved' OR a.status = 1 OR a.status = '已核可')
            AND st.department = '$dept_name'";

    // Just printing SQL for debugging, not running prepared statement here for simplicity of text output
    // echo "Querying for $dept_name: $sql\n";

    $stmt = $conn->prepare("SELECT SUM(s.amount) as total_used
            FROM applications a
            JOIN students st ON a.student_username = st.username
            JOIN scholarships s ON a.scholarship_id = s.id
            WHERE (a.status = 'approved' OR a.status = 1 OR a.status = '已核可')
            AND st.department = ?");
    $stmt->bind_param("s", $dept_name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $used = $row['total_used'] ?? 0;
    echo "Dept: $dept_name, Used: $used\n";
}

$conn->close();
?>