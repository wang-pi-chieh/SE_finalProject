<?php
// Centralized storage helpers for runtime uploads.
// Zeabur should set UPLOADS_BASE_DIR to the mounted volume path, e.g. /data/uploads.
// When the variable is not set, keep the original project-local uploads path for local tests.

function upload_storage_project_root()
{
    return dirname(__DIR__, 2);
}

function upload_storage_env($keys, $default = '')
{
    foreach ((array) $keys as $key) {
        $value = getenv($key);
        if ($value !== false && trim((string) $value) !== '') {
            return rtrim(str_replace('\\', '/', trim((string) $value)), '/');
        }
    }
    return $default;
}

function upload_storage_base_dir()
{
    static $baseDir = null;
    if ($baseDir !== null) {
        return $baseDir;
    }

    $configured = upload_storage_env(['UPLOADS_BASE_DIR', 'UPLOAD_BASE_DIR', 'UPLOAD_DIR']);
    if ($configured !== '') {
        $baseDir = $configured;
        return $baseDir;
    }

    $baseDir = upload_storage_project_root() . DIRECTORY_SEPARATOR . 'uploads';
    return $baseDir;
}

function upload_storage_ensure_dir($subdir = '')
{
    $baseDir = upload_storage_base_dir();
    $targetDir = $baseDir;
    $subdir = trim(str_replace('\\', '/', (string) $subdir), '/');
    if ($subdir !== '') {
        $targetDir .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subdir);
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new Exception('無法建立上傳目錄：' . $targetDir);
    }

    return $targetDir;
}

function upload_storage_normalize_relative_path($path)
{
    $path = trim(str_replace('\\', '/', (string) $path));
    if ($path === '') {
        return null;
    }

    $path = preg_replace('#^(\./)+#', '', $path);
    while (strpos($path, '../') === 0) {
        $path = substr($path, 3);
    }
    $path = ltrim($path, '/');

    $parts = array_values(array_filter(explode('/', $path), static function ($part) {
        return $part !== '';
    }));

    if (count($parts) < 2 || $parts[0] !== 'uploads') {
        return null;
    }

    foreach ($parts as $part) {
        if ($part === '..') {
            return null;
        }
    }

    return implode('/', $parts);
}

function upload_storage_absolute_path($relativePath)
{
    $relativePath = upload_storage_normalize_relative_path($relativePath);
    if ($relativePath === null) {
        return null;
    }

    $parts = explode('/', $relativePath);
    array_shift($parts);
    return upload_storage_base_dir() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
}

function upload_storage_public_path($subdir, $filename)
{
    $subdir = trim(str_replace('\\', '/', (string) $subdir), '/');
    $filename = basename((string) $filename);
    return $subdir === '' ? 'uploads/' . $filename : 'uploads/' . $subdir . '/' . $filename;
}

function upload_storage_move_uploaded_file($tmpPath, $subdir, $filename)
{
    $targetDir = upload_storage_ensure_dir($subdir);
    $filename = basename((string) $filename);
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        return null;
    }

    return upload_storage_public_path($subdir, $filename);
}

function upload_storage_download_url($relativePath, $forceDownload = false)
{
    $relativePath = upload_storage_normalize_relative_path($relativePath);
    if ($relativePath === null) {
        return '';
    }

    $url = '../api/download_application_file.php?file=' . rawurlencode($relativePath);
    return $forceDownload ? $url . '&download=1' : $url;
}
