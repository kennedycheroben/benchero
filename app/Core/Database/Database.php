<?php

namespace Benchero\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', 3306);
            $db   = env('DB_DATABASE', 'benchero_dev');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', '');
            $charset = env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
                self::$instance->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $e) {
                // Do not leak credentials in exception message
                echo $e->getMessage() . "\n";
                throw new RuntimeException('Database connection failed.');
            }
        }

        return self::$instance;
    }

    public static function query(string $sql, array $params = []): false|\PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
