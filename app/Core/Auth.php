<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || strlen($email) > 190 || $password === '') {
            return false;
        }

        $pdo = Database::connection();
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $limit = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE (email = :email OR ip_address = :ip)
               AND attempted_at >= (CURRENT_TIMESTAMP - INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)'
        );
        $limit->execute(['email' => $email, 'ip' => $ip]);
        if ((int) $limit->fetchColumn() >= self::MAX_ATTEMPTS) {
            return false;
        }

        $query = $pdo->prepare(
            'SELECT id, name, email, password_hash, status
             FROM users WHERE email = :email LIMIT 1'
        );
        $query->execute(['email' => $email]);
        $user = $query->fetch();

        $valid = is_array($user)
            && $user['status'] === 'active'
            && password_verify($password, (string) $user['password_hash']);

        if (!$valid) {
            $record = $pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
            $record->execute(['email' => $email, 'ip' => $ip]);
            return false;
        }

        $membership = $pdo->prepare(
            "SELECT 1 FROM tenant_memberships
             WHERE user_id = :user_id AND status = 'active' LIMIT 1"
        );
        $membership->execute(['user_id' => $user['id']]);
        $platformRole = $pdo->prepare(
            "SELECT 1 FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id
               AND ur.tenant_id IS NULL AND ur.organisation_id IS NULL
               AND r.role_key = 'platform_super_admin'
             LIMIT 1"
        );
        $platformRole->execute(['user_id' => $user['id']]);

        if (!$membership->fetchColumn() && !$platformRole->fetchColumn()) {
            $record = $pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
            $record->execute(['email' => $email, 'ip' => $ip]);
            return false;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = (int) $user['id'];
        $_SESSION['auth_user_name'] = (string) $user['name'];
        $_SESSION['auth_user_email'] = (string) $user['email'];
        unset($_SESSION['_csrf_token']);

        $update = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => $user['id']]);
        $cleanup = $pdo->prepare(
            'DELETE FROM login_attempts WHERE attempted_at < (CURRENT_TIMESTAMP - INTERVAL 1 DAY)'
        );
        $cleanup->execute();
        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth_user_id']) && is_int($_SESSION['auth_user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['auth_user_id'],
            'name' => $_SESSION['auth_user_name'] ?? '',
            'email' => $_SESSION['auth_user_email'] ?? '',
        ];
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
