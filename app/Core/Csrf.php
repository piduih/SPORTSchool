<?php
declare(strict_types=1);

namespace App\\Core;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function verify(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !is_string($token)) {
            return false;
        }
        $known = $_SESSION['_csrf_token'] ?? '';
        return is_string($known) && $known !== '' && hash_equals($known, $token);
    }
}
