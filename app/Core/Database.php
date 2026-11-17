<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = trim((string) getenv('DB_HOST'));
        $port = filter_var(getenv('DB_PORT') ?: '3306', FILTER_VALIDATE_INT);
        $name = trim((string) getenv('DB_DATABASE'));
        $username = (string) getenv('DB_USERNAME');
        $password = (string) getenv('DB_PASSWORD');
        $charset = strtolower(trim((string) (getenv('DB_CHARSET') ?: 'utf8mb4')));

        if ($host === '' || preg_match('/^[a-zA-Z0-9.-]+$/', $host) !== 1) {
            throw new RuntimeException('DB_HOST is missing or invalid.');
        }
        if ($port === false || $port < 1 || $port > 65535) {
            throw new RuntimeException('DB_PORT must be between 1 and 65535.');
        }
        if ($name === '' || preg_match('/^[a-zA-Z0-9_]+$/', $name) !== 1) {
            throw new RuntimeException('DB_DATABASE is missing or invalid.');
        }
        if ($username === '') {
            throw new RuntimeException('DB_USERNAME is not configured.');
        }
        if ($charset !== 'utf8mb4') {
            throw new RuntimeException('Only utf8mb4 is supported for database connections.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $name,
            $charset
        );

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_PERSISTENT => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed. Check server configuration and database availability.', 0, $exception);
        }

        return self::$connection;
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }
}
