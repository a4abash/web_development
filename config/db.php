<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

class Database {
    private static ?mysqli $connection = null;

    public static function connect(array $config): void {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            self::$connection = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['db'],
                $config['port']
            );

            self::$connection->set_charset("utf8mb4");
            self::$connection->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            self::$connection->query("SET time_zone = '" . $config['timezone'] . "'");
        } catch (mysqli_sql_exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            exit("Database connection failed. Please try again later.");
        }
    }

    public static function getConnection(): mysqli {
        if (!self::$connection) {
            throw new RuntimeException('Database connection not established');
        }
        return self::$connection;
    }
}

$config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'password',
    'db' => 'abash',
    'port' => 3306,
    'timezone' => '+05:45'
];

try {
    Database::connect($config);
    $conn = Database::getConnection();
} catch (RuntimeException $e) {
    exit($e->getMessage());
}
        