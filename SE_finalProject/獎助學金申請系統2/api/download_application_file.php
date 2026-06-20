<?php
// Securely serves application upload files referenced by application review APIs.
require_once __DIR__ . '/common/upload_storage.php';

function fail_download($statusCode, $message)
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

$relativePath = upload_storage_normalize_relative_path($_GET['file'] ?? '');
if ($relativePath === null) {
    fail_download(400, '檔案路徑不正確');
}

$uploadsRoot = realpath(upload_storage_base_dir());
if ($uploadsRoot === false) {
    fail_download(404, '線上 uploads 目錄不存在');
}

$absolutePath = upload_storage_absolute_path($relativePath);
$targetPath = $absolutePath ? realpath($absolutePath) : false;
$requiredPrefix = $uploadsRoot . DIRECTORY_SEPARATOR;
if (
    $targetPath === false
    || strncmp($targetPath, $requiredPrefix, strlen($requiredPrefix)) !== 0
    || !is_file($targetPath)
) {
    fail_download(404, '線上檔案不存在');
}

$mimeType = function_exists('mime_content_type') ? mime_content_type($targetPath) : false;
if (!$mimeType) {
    $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $mimeMap = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
    ];
    $mimeType = $mimeMap[$extension] ?? 'application/octet-stream';
}

$downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($targetPath));
$downloadMode = strtolower(trim((string) ($_GET['download'] ?? $_GET['disposition'] ?? '')));
$forceDownload = in_array($downloadMode, ['1', 'true', 'yes', 'download', 'attachment'], true);
$contentDisposition = $forceDownload ? 'attachment' : 'inline';

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($targetPath));
header('Content-Disposition: ' . $contentDisposition . '; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
readfile($targetPath);
