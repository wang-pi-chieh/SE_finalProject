<?php
require_once __DIR__ . '/../common/upload_storage.php';

function admin_backup_subdir()
{
    return 'admin_backups';
}

function admin_backup_prepare_target($filename)
{
    $filename = basename((string) $filename);
    if ($filename === '') {
        throw new Exception('備份檔名不正確。');
    }

    $base_dir = upload_storage_base_dir();
    $target_dir = $base_dir . DIRECTORY_SEPARATOR . admin_backup_subdir();

    if (!is_dir($target_dir) && !@mkdir($target_dir, 0777, true) && !is_dir($target_dir)) {
        throw new Exception('無法建立備份資料夾，請確認 UPLOADS_BASE_DIR 可寫入。');
    }

    if (!is_writable($target_dir)) {
        throw new Exception('備份資料夾不可寫入，請確認 UPLOADS_BASE_DIR 權限。');
    }

    return [
        'absolute_path' => $target_dir . DIRECTORY_SEPARATOR . $filename,
        'relative_path' => upload_storage_public_path(admin_backup_subdir(), $filename),
    ];
}

function admin_backup_resolve_file_path($stored_path)
{
    $stored_path = trim(str_replace('\\', '/', (string) $stored_path));
    if ($stored_path === '') {
        return null;
    }

    $upload_relative = upload_storage_normalize_relative_path($stored_path);
    if ($upload_relative !== null) {
        $uploads_root = realpath(upload_storage_base_dir());
        $absolute_path = upload_storage_absolute_path($upload_relative);
        $file_path = $absolute_path ? realpath($absolute_path) : false;

        if ($uploads_root === false || $file_path === false) {
            return null;
        }

        $required_prefix = rtrim($uploads_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strncmp($file_path, $required_prefix, strlen($required_prefix)) !== 0 || !is_file($file_path)) {
            return null;
        }

        return $file_path;
    }

    return admin_backup_resolve_legacy_file_path($stored_path);
}

function admin_backup_resolve_legacy_file_path($stored_path)
{
    $legacy_relative = ltrim(str_replace('\\', '/', (string) $stored_path), '/');
    if (strpos($legacy_relative, 'backups/') !== 0 || strpos($legacy_relative, '..') !== false) {
        return null;
    }

    $project_root = realpath(dirname(__DIR__, 2));
    $backup_root = $project_root ? realpath($project_root . DIRECTORY_SEPARATOR . 'backups') : false;
    $candidate = $project_root ? realpath($project_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $legacy_relative)) : false;

    if ($backup_root === false || $candidate === false) {
        return null;
    }

    $required_prefix = rtrim($backup_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncmp($candidate, $required_prefix, strlen($required_prefix)) !== 0 || !is_file($candidate)) {
        return null;
    }

    return $candidate;
}

function admin_backup_write_zip($zip_path, $entries)
{
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return is_file($zip_path) && filesize($zip_path) > 0;
    }

    return admin_backup_write_zip_with_php($zip_path, $entries);
}

function admin_backup_write_zip_with_php($zip_path, $entries)
{
    if (!is_array($entries) || count($entries) === 0) {
        return false;
    }

    $handle = @fopen($zip_path, 'wb');
    if (!$handle) {
        return false;
    }

    $central_directory = '';
    $offset = 0;
    $now = admin_backup_dos_time_date(time());

    foreach ($entries as $name => $contents) {
        $name = ltrim(str_replace('\\', '/', (string) $name), '/');
        if ($name === '' || strpos($name, '..') !== false) {
            fclose($handle);
            @unlink($zip_path);
            return false;
        }

        $contents = (string) $contents;
        $name_length = strlen($name);
        $size = strlen($contents);
        $crc = crc32($contents);
        if ($crc < 0) {
            $crc += 4294967296;
        }

        $local_header = pack(
            'VvvvvvVVVvv',
            0x04034b50,
            20,
            0,
            0,
            $now['time'],
            $now['date'],
            $crc,
            $size,
            $size,
            $name_length,
            0
        ) . $name;

        if (@fwrite($handle, $local_header) === false || @fwrite($handle, $contents) === false) {
            fclose($handle);
            @unlink($zip_path);
            return false;
        }

        $central_directory .= pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014b50,
            20,
            20,
            0,
            0,
            $now['time'],
            $now['date'],
            $crc,
            $size,
            $size,
            $name_length,
            0,
            0,
            0,
            0,
            0,
            $offset
        ) . $name;

        $offset += strlen($local_header) + $size;
    }

    $central_size = strlen($central_directory);
    if (@fwrite($handle, $central_directory) === false) {
        fclose($handle);
        @unlink($zip_path);
        return false;
    }

    $entry_count = count($entries);
    $end_record = pack(
        'VvvvvVVv',
        0x06054b50,
        0,
        0,
        $entry_count,
        $entry_count,
        $central_size,
        $offset,
        0
    );

    if (@fwrite($handle, $end_record) === false) {
        fclose($handle);
        @unlink($zip_path);
        return false;
    }

    fclose($handle);
    return is_file($zip_path) && filesize($zip_path) > 0;
}

function admin_backup_dos_time_date($timestamp)
{
    $parts = getdate($timestamp);
    $year = max(1980, (int) $parts['year']);

    return [
        'time' => ((int) $parts['hours'] << 11) | ((int) $parts['minutes'] << 5) | ((int) floor($parts['seconds'] / 2)),
        'date' => (($year - 1980) << 9) | ((int) $parts['mon'] << 5) | (int) $parts['mday'],
    ];
}
?>
