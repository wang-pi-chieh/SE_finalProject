<?php
require 'api/db_connect.php';
require 'api/log_utils.php';

logAction('System Tester', '測試紀錄', '这是一条测试日志');
echo "Log inserted.\n";
?>