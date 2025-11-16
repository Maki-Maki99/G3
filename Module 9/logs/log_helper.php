<?php
// Module9/logs/log_helper.php
// Simple append-only logger for integration tests and errors.

function module9_log($filename, $line) {
    $dir = __DIR__;
    $fullpath = $dir . DIRECTORY_SEPARATOR . $filename;
    // Ensure logs directory exists (should already)
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ts = date('Y-m-d H:i:s');
    $entry = "[$ts] " . $line . PHP_EOL;
    file_put_contents($fullpath, $entry, FILE_APPEND | LOCK_EX);
}