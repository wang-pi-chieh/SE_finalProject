<?php   
require_once("../../db.php");
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$dbname = "nsams"; // ⚠️ 依你們實際 DB 名稱
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 👉 先抓最基本資料（之後可以再擴充 join）
    $sql = "
        SELECT 
            id,
            student_name,
            scholarship_name,
            status
        FROM applications
        WHERE status = 'pending'
        ORDER BY id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "DB error",
        "error" => $e->getMessage()
    ]);
}
?>