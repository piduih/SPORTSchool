<?php
declare(strict_types=1);

namespace App\\Core;

use PDO;
use RuntimeException;
use Throwable;

final class Database
{
    public static function connection(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_DATABASE') ?: '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        if ($name === '') {
            throw new RuntimeException('DB_DATABASE is not configured.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        try {
            return new PDO($dsn, getenv('DB_USERNAME') ?: '', getenv('DB_PASSWORD') ?: '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException('Database connection failed.', 0, $e);
        }
    }
}
