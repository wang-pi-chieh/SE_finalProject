<?php
header('Content-Type: application/json; charset=utf-8');

// ====== 1. 連線資料庫 ======
$host = "localhost";
$dbname = "nsams"; // ⚠️ 如果你們不是這個名字要改
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "DB connection failed",
        "error" => $e->getMessage()
    ]);
    exit;
}

// ====== 2. 取得前端資料 ======
$application_id = $_POST['application_id'] ?? null;
$reviewer_id    = $_POST['reviewer_id'] ?? null;
$score          = $_POST['score'] ?? null;
$comment        = $_POST['comment'] ?? "";

// ====== 3. 基本驗證 ======
if (!$application_id || !$reviewer_id || $score === null) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

// score 限制（避免亂填）
if (!is_numeric($score) || $score < 0 || $score > 100) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid score (0-100)"
    ]);
    exit;
}

// ====== 4. 寫入 review_records ======
try {
    $sql = "
        INSERT INTO review_records
        (application_id, reviewer_id, score, comment, created_at)
        VALUES
        (:application_id, :reviewer_id, :score, :comment, NOW())
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":application_id" => $application_id,
        ":reviewer_id"    => $reviewer_id,
        ":score"          => $score,
        ":comment"        => $comment
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Review submitted successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Insert failed",
        "error" => $e->getMessage()
    ]);
}
?>