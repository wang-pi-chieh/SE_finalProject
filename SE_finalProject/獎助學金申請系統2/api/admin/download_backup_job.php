<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/_admin_ops_common.php';
require_once __DIR__ . '/_backup_storage.php';

admin_ops_require_table($conn, 'backup_jobs');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid backup job id';
    exit;
}

$stmt = $conn->prepare("SELECT job_name, status, file_path FROM backup_jobs WHERE id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo 'Failed to prepare backup lookup';
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$job) {
    http_response_code(404);
    echo 'Backup job not found';
    exit;
}

if ($job['status'] !== 'completed' || trim((string) $job['file_path']) === '') {
    http_response_code(409);
    echo 'Backup job is not completed';
    exit;
}

$file_path = admin_backup_resolve_file_path($job['file_path']);
if ($file_path === null) {
    http_response_code(404);
    echo 'Backup file not found';
    exit;
}

$download_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $job['job_name']) . '.zip';

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Pragma: no-cache');
header('Expires: 0');

if (ob_get_level()) {
    ob_end_clean();
}

readfile($file_path);
exit;
?>
