<?php
declare(strict_types=1);
namespace App\Core;
use RuntimeException;
final class Config
{
    private static array $items = [];
    public static function load(string $basePath): void
    {
        $items = require $basePath . '/config/app.php';
        if (($items['env'] ?? 'production') === 'production') {
            if (strlen((string) ($items['key'] ?? '')) < 32) {
                throw new RuntimeException('APP_KEY must contain at least 32 characters in production.');
            }
            if (($items['debug'] ?? false) === true) {
                throw new RuntimeException('APP_DEBUG must be false in production.');
            }
        }
        self::$items = $items;
    }
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $default;
    }
    public static function isDebug(): bool { return self::get('debug', false) === true; }
    public static function isProduction(): bool { return self::get('env', 'production') === 'production'; }
}
