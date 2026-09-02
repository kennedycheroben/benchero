<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv->load();
}

// Error handling based on environment
if (env('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    // Setup production error logging here
    $logPath = __DIR__ . '/../storage/logs/error.log';
    if (!is_dir(dirname($logPath))) {
        mkdir(dirname($logPath), 0755, true);
    }
    ini_set('error_log', $logPath);
}

// Set timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Nairobi'));
