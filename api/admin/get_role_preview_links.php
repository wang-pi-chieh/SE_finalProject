<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';

ensure_preview_accounts($conn);

$links = [
    [
        'role' => 'student',
        'label' => '學生端',
        'url' => '../student/student-dashboard.html?preview=student&preview_user=student-preview'
    ],
    [
        'role' => 'teacher',
        'label' => '老師端',
        'url' => '../teacher/teacher-dashboard.html?preview=teacher&preview_user=teacher-preview'
    ],
    [
        'role' => 'reviewer',
        'label' => '審查單位端',
        'url' => '../reviewer/reviewer-dashboard.html?preview=reviewer&preview_user=reviewer-preview'
    ]
];

admin_ops_json(['success' => true, 'data' => $links]);

function ensure_preview_accounts($conn)
{
    ensure_preview_user($conn, 'student-preview', '學生', '學生端預覽', 'student-preview@example.edu');
    ensure_preview_user($conn, 'teacher-preview', '老師', '老師端預覽', 'teacher-preview@example.edu');
    ensure_preview_user($conn, 'reviewer-preview', '獎助單位', '審查單位端預覽', 'reviewer-preview@example.edu');

    $stmt = $conn->prepare("INSERT IGNORE INTO students (username, department, grade_level, class_name) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $username = 'student-preview';
        $department = '預覽系所';
        $grade = '預覽年級';
        $class_name = '預覽班級';
        $stmt->bind_param("ssss", $username, $department, $grade, $class_name);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO teachers (username, department) VALUES (?, ?)");
    if ($stmt) {
        $username = 'teacher-preview';
        $department = '預覽系所';
        $stmt->bind_param("ss", $username, $department);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO scholarship_units (username, unit_name, person_in_charge) VALUES (?, ?, ?)");
    if ($stmt) {
        $username = 'reviewer-preview';
        $unit_name = '審查單位端預覽';
        $person = '預覽承辦人';
        $stmt->bind_param("sss", $username, $unit_name, $person);
        $stmt->execute();
        $stmt->close();
    }
}

function ensure_preview_user($conn, $username, $role, $real_name, $email)
{
    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, role, real_name, password, phone, email) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return;
    }

    $password = 'preview';
    $phone = '0900000000';
    $stmt->bind_param("ssssss", $username, $role, $real_name, $password, $phone, $email);
    $stmt->execute();
    $stmt->close();
}
?>
