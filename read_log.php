<?php
$logFile = 'storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file not found.\n";
    exit;
}

$lines = file($logFile);
$lastLines = array_slice($lines, -50);
foreach ($lastLines as $line) {
    if (strpos($line, 'local.ERROR') !== false || strpos($line, 'Stack trace') !== false || strpos($line, '#0') !== false) {
        echo $line;
    }
}
