<?php
declare(strict_types=1);
$basePath = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($basePath): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = $basePath . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});
$envFile = $basePath . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_starts_with($line, 'export ')) $line = substr($line, 7);
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            if (str_ends_with($value, $quote) && strlen($value) >= 2) $value = substr($value, 1, -1);
        } else {
            $value = trim(preg_replace('/\s+#.*$/', '', $value) ?? $value);
        }
        if ($key !== '' && preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}
\App\Core\Config::load($basePath);
date_default_timezone_set((string) \App\Core\Config::get('timezone', 'Asia/Kuala_Lumpur'));
error_reporting(E_ALL);
ini_set('display_errors', \App\Core\Config::isDebug() ? '1' : '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Application error. Check the server error log.\n");
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    if (\App\Core\Config::isDebug()) {
        echo $exception;
        return;
    }
    echo 'An unexpected error occurred. Please try again later.';
});
if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') {
    $isHttps = isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    session_name('SPORTSCHOOLSESSID');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $isHttps,
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}
return $basePath;
