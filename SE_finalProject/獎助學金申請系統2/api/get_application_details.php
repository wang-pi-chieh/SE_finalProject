<?php
require_once 'db_connect.php';
require_once __DIR__ . '/reviewer/_review_award_common.php';
require_once __DIR__ . '/common/upload_storage.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

try {
    if (!isset($_GET['id'])) {
        throw new Exception("缺少申請編號");
    }

    $application_id = intval($_GET['id']);
    reviewer_award_ensure_schema($conn);

    // 1. Fetch Application & Student & Scholarship Info
    $sql = "SELECT 
                a.*,
                a.status, a.review_comment,
                s.name as scholarship_name,
                u.real_name as student_name,
                u.email as student_email,
                st.department,
                st.grade_level,
                rl.content as recommendation_content,
                rl.file_path as recommendation_file,
                rl.status as recommendation_status
            FROM applications a
            JOIN scholarships s ON a.scholarship_id = s.id
            JOIN users u ON a.student_username = u.username
            LEFT JOIN students st ON a.student_username = st.username
            LEFT JOIN reference_letters rl ON a.id = rl.application_id
            WHERE a.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("找不到該申請紀錄");
    }

    $application = $result->fetch_assoc();

    // Pass raw status (0, 1, 2, 3)
    // $application['status'] is already set from DB fetch.

    // 2. Fetch Real Grades
    $grades_sql = "SELECT academic_year, semester, avg_score, gpa, class_rank, class_size 
                   FROM grades 
                   WHERE student_username = ? 
                   ORDER BY academic_year DESC, CASE WHEN semester = '下' THEN 2 ELSE 1 END DESC";
    $grades_stmt = $conn->prepare($grades_sql);
    $grades_stmt->bind_param("s", $application['student_username']);
    $grades_stmt->execute();
    $grades_result = $grades_stmt->get_result();
    $real_grades = [];
    while ($row = $grades_result->fetch_assoc()) {
        $real_grades[] = $row;
    }
    $grades_stmt->close();

    // 3. Process Documents & Biography
    $documents = [];
    $bio_files = [];
    $bio_content = '';
    $bio_is_file = false;

    $app_date = isset($application['application_date']) ? date('Y-m-d', strtotime($application['application_date'])) : date('Y-m-d');

    // Helper closure to format file object and expose real file availability.
    $formatFile = function ($path, $defaultName) use ($app_date) {
        $relativePath = upload_storage_normalize_relative_path($path);
        $originalName = $relativePath ? basename($relativePath) : basename((string) $path);
        $exists = false;
        $downloadUrl = '';
        $missingReason = '檔案路徑格式不正確';

        if ($relativePath) {
            $absolutePath = upload_storage_absolute_path($relativePath);
            $exists = is_file($absolutePath);
            $downloadUrl = upload_storage_download_url($relativePath, true);
            $missingReason = $exists ? '' : '線上檔案不存在';
        }

        return [
            'name' => $defaultName,
            'original_name' => $originalName,
            'url' => $downloadUrl,
            'relative_path' => $relativePath,
            'exists' => $exists,
            'missing_reason' => $missingReason,
            'type' => pathinfo($originalName, PATHINFO_EXTENSION),
            'date' => $app_date,
            'size' => 'Unknown'
        ];
    };

    // Check Biography (Text or File or JSON Files)
    $bio_raw = $application['biography'] ?? '';
    $bio_decoded = json_decode($bio_raw, true);

    if (is_array($bio_decoded)) {
        // It's a list of files
        foreach ($bio_decoded as $path) {
            if (is_string($path)) {
                $bio_files[] = $formatFile($path, '自傳/讀書計畫');
            }
        }
    } elseif (!empty($bio_raw) && strpos($bio_raw, 'uploads/') !== false) {
        // Single file path
        $bio_files[] = $formatFile($bio_raw, '自傳/讀書計畫');
    } else {
        // Text content
        $bio_content = nl2br($bio_raw ?? '無自傳內容');
    }

    if (count($bio_files) > 0) {
        $bio_is_file = true;
    }

    // Check Other Documents
    $other_raw = $application['application_documents'] ?? '';
    $other_decoded = json_decode($other_raw, true);

    if (is_array($other_decoded)) {
        foreach ($other_decoded as $path) {
            if (is_string($path)) {
                $documents[] = $formatFile($path, '其他有利審查資料');
            }
        }
    } elseif (!empty($other_raw) && strpos($other_raw, 'uploads/') !== false) {
        $documents[] = $formatFile($other_raw, '其他有利審查資料');
    }

    $recommendation_file = '';
    $recommendation_file_path = upload_storage_normalize_relative_path($application['recommendation_file'] ?? '');
    if ($recommendation_file_path !== null) {
        $recommendation_file = upload_storage_download_url($recommendation_file_path, true);
    }

    // 4. Determine latest GPA for profile
    $gpa = (count($real_grades) > 0) ? $real_grades[0]['gpa'] : '0.00';

    // Response structure
    $response = [
        'success' => true,
        'data' => [
            'application' => [
                'id' => $application['id'],
                'year_semester' => ($application['academic_year'] ?? '113') . '學年度 第' . ($application['semester'] ?? '1') . '學期',
                'scholarship_name' => $application['scholarship_name'],
                'application_date' => $application['application_date'],
                'prev_award' => $application['previous_scholarship_name'] ?? '無',
                'referrer' => ($application['referrer_relationship'] ?? '') . ' (推薦人：' . ($application['referrer_name'] ?? '無') . ')',
                'status' => $application['status'] ?? 'pending',
                'review_comment' => $application['review_comment'] ?? '',
                'review_score' => $application['review_score'] !== null ? (float) $application['review_score'] : null,
                'biography' => $bio_content,
                'biography_files' => $bio_files,
                'bio_is_file' => $bio_is_file,

                // Family
                'family_housing' => $application['family_housing_status'] ?? '未填寫',
                'personal_housing' => $application['personal_housing_status'] ?? '未填寫',
                'student_loan' => $application['has_student_loan'] === '是' || $application['has_student_loan'] === 'yes' ? '有' : '無',
                'tuition_waiver' => $application['tuition_waiver'] ?? '無',
                'family_desc' => nl2br($application['family_situation_desc'] ?? '無'),
                'family_members' => nl2br($application['family_members_desc'] ?? '無'),
                'recommendation' => [
                    'required' => $application['recommendation_required'] ?? 0,
                    'content' => $application['recommendation_content'] ?? '',
                    'file' => $recommendation_file,
                    'status' => $application['recommendation_status'] ?? 'pending'
                ],
                'phone' => $application['phone'] ?? '',
                'bank_account' => $application['bank_account'] ?? '',
            ],
            'student' => [
                'name' => $application['student_name'],
                'id' => $application['student_username'],
                'dept' => $application['department'] ?? '未填寫',
                'grade' => $application['grade_level'] ?? '未填寫',
                'gpa' => $gpa,
                'email' => $application['student_email'],
                'avatar_letter' => mb_substr($application['student_name'], 0, 1, 'UTF-8')
            ],
            'grades' => $real_grades,
            'documents' => $documents
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
