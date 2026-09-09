<?php

/** Record a log message to the server log files
 * 
 * @param string $message The message to log.
 * @param string $level The log level (e.g., 'info', 'error', 'warning'). Default is 'info'.
 * @return void
 */
function recordLogs(string $message, string $level = 'info')
{
    // Use server logs directory (mounted from host)
    $logFolder = "/var/www/logs/application/";
    $logFilePath = $logFolder . date("Y-m-d") . ".log";

    if (!is_dir($logFolder)) {
        mkdir($logFolder, 0755, true);
    }

    if (file_exists($logFilePath) && filesize($logFilePath) > 5 * 1024 * 1024) {
        rename($logFilePath, $logFolder . date("Y-m-d_H-i-s") . ".log");
    }

    $timestamp = date("Y-m-d H:i:s");
    $formattedMessage = "[$timestamp] [$level]: $message" . PHP_EOL;
    file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);
}
