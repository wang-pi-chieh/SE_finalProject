<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// Check Authentication
// login.php sets $_SESSION['role'] directly
if (!isset($_SESSION['role'])) {
    // Fallback: Check if user is logged in via frontend localStorage but session died
    // In a real app we'd redirect to login, but here for backup download we return 403
    http_response_code(403);
    die('Access Denied: No session');
}

$role = $_SESSION['role'];
if ($role !== 'system_admin' && $role !== '系統管理員') {
    http_response_code(403);
    die('Access Denied: System Admin only');
}

// Configuration
$backup_name = 'backup_scholarship_system_' . date('Y-m-d_H-i-s') . '.zip';
$sql_file_name = 'database_dump.sql';

// 1. Generate SQL Dump
$sql_content = "";
$sql_content .= "-- Scholarship System Database Dump\n";
$sql_content .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
$sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Structure
    $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
    $sql_content .= "-- Table structure for `$table`\n";
    $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
    $sql_content .= $row2[1] . ";\n\n";

    // Data
    $result3 = $conn->query("SELECT * FROM $table");
    $num_fields = $result3->field_count;

    $sql_content .= "-- Dumping data for table `$table`\n";
    while ($row = $result3->fetch_row()) {
        $sql_content .= "INSERT INTO `$table` VALUES(";
        for ($j = 0; $j < $num_fields; $j++) {
            $row[$j] = addslashes($row[$j]);
            $row[$j] = str_replace("\n", "\\n", $row[$j]);
            if (isset($row[$j])) {
                $sql_content .= '"' . $row[$j] . '"';
            } else {
                $sql_content .= '""';
            }
            if ($j < ($num_fields - 1)) {
                $sql_content .= ',';
            }
        }
        $sql_content .= ");\n";
    }
    $sql_content .= "\n\n";
}
$sql_content .= "SET FOREIGN_KEY_CHECKS=1;\n";

// 2. Create ZIP
$zip_created = false;
$temp_zip = tempnam(sys_get_temp_dir(), 'zip');
$rootPath = realpath(__DIR__ . '/../');

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($temp_zip, ZipArchive::CREATE) === TRUE) {
        // Add SQL file
        $zip->addFromString($sql_file_name, $sql_content);

        // Add Project Files
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                if (strpos($relativePath, '.git') === 0)
                    continue;
                if (strpos($relativePath, 'node_modules') === 0)
                    continue;
                if (strpos($relativePath, 'vendor') === 0)
                    continue;
                if (strpos($filePath, $temp_zip) !== false)
                    continue; // Exclude self if in path

                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        $zip_created = true;
    }
} else {
    // Fallback: Try PowerShell Compress-Archive (Windows only)
    // Save SQL to file first so it can be included
    $temp_sql = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $sql_file_name;
    file_put_contents($temp_sql, $sql_content);

    // We want to zip $rootPath content AND $temp_sql
    // This is tricky with one command. Let's just zip the root path and then we might miss the SQL if we can't add it easily.
    // Simpler: Just zip the root path. The database dump is CRITICAL, so we must include it.
    // Let's copy SQL to root temporarily? No, dangerous.

    // Strategy: Zip root path to temp, then serve. AND try to include SQL.
    // PowerShell Compress-Archive can take multiple paths.
    // Command: Compress-Archive -Path "C:\Source\*", "C:\Temp\db.sql" -DestinationPath "C:\Temp\backup.zip"

    $cmd_source = escapeshellarg($rootPath . DIRECTORY_SEPARATOR . "*");
    $cmd_sql = escapeshellarg($temp_sql);
    $cmd_dest = escapeshellarg($temp_zip . ".zip"); // PowerShell needs .zip extension usually

    // Note: PowerShell command might fail if execution policy restricts, or if path has spaces (handled by escapeshellarg hopefully)
    // But escapeshellarg on Windows surrounds with double quotes, which works for PowerShell.

    // We need to remove the temp file made by tempnam first because Compress-Archive might complain if dest exists? 
    // Actually tempnam creates a file. Compress-Archive -Update? Or -Force
    @unlink($temp_zip);
    $temp_zip = $temp_zip . ".zip"; // Append extension for powershell

    // Exec
    $cmd = "powershell -Command \"Compress-Archive -Path $cmd_source, $cmd_sql -DestinationPath $cmd_dest -Force\"";
    exec($cmd, $output, $return_var);

    // Cleanup temp SQL
    @unlink($temp_sql);

    if ($return_var === 0 && file_exists($temp_zip)) {
        $zip_created = true;
    }
}

// 3. Serve File
if ($zip_created && file_exists($temp_zip)) {
    // Log backup creation
    require_once 'log_utils.php';
    $actor = $_SESSION['username'] ?? 'System Admin'; // Assuming username is in session, role was checked earlier
    logAction($actor, '系統備份', "下載完整備份: $backup_name");

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $backup_name . '"');
    header('Content-Length: ' . filesize($temp_zip));
    header('Pragma: no-cache');
    header('Expires: 0');

    if (ob_get_level())
        ob_end_clean();
    readfile($temp_zip);

    @unlink($temp_zip);
    exit;
} else {
    // Final Fallback: Download SQL Only
    // If ZIP failed, at least give them the database
    $fallback_name = 'database_dump_' . date('Y-m-d_H-i-s') . '.sql';

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $fallback_name . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (ob_get_level())
        ob_end_clean();
    echo $sql_content;

    // Optionally log error
    // error_log("Zip creation failed. Served SQL only.");
    exit;
}
?>