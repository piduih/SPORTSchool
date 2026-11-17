<?php
declare(strict_types=1);
namespace App\Core;
final class Config
{
    private static array $items = [];
    public static function load(string $basePath): void
    {
        self::$items = require $basePath . '/config/app.php';
    }
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $default;
    }
}
