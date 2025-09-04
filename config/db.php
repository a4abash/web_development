<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

$host =  "localhost";
$user = "root";
$pass = "password";
$db   = "abash";
$port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);

    $conn->set_charset("utf8mb4");

    $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

    $conn->query("SET time_zone = '+05:45'");
    print_r($_ENV['DB_HOST']);
    echo "Database connected successfully";
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());

    exit("Service temporarily unavailable. Please try again later.");
}
