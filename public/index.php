<?php
declare(strict_types=1);

$basePath = require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

function respondHtml(string $title, string $body): never
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ms"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</title></head><body><main><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</h1>' . $body . '</main></body></html>';
    exit;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($path === '/health' && $method === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        Database::connection()->query('SELECT 1');
        echo json_encode(['application' => 'SPORTSchool', 'status' => 'ok', 'database' => 'connected']);
    } catch (Throwable $e) {
        http_response_code(503);
        error_log('SPORTSchool health check failed: ' . $e->getMessage());
        echo json_encode(['application' => 'SPORTSchool', 'status' => 'unavailable', 'database' => 'unavailable']);
    }
    exit;
}

if ($path === '/login' && $method === 'GET') {
    if (Auth::check()) {
        header('Location: /');
        exit;
    }
    $token = Csrf::token();
    $body = '<form method="post" action="/login">'
        . '<input type="hidden" name="_token" value="' . escape($token) . '">'
        . '<p><label>E-mel <input type="email" name="email" maxlength="190" autocomplete="username" required></label></p>'
        . '<p><label>Kata laluan <input type="password" name="password" autocomplete="current-password" required></label></p>'
        . '<button type="submit">Log masuk</button></form>';
    respondHtml('Log masuk SPORTSchool', $body);
}

if ($path === '/login' && $method === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        http_response_code(419);
        respondHtml('Permintaan tidak sah', '<p>Sila muat semula halaman dan cuba lagi.</p>');
    }
    $email = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (Auth::attempt($email, $password)) {
        header('Location: /');
        exit;
    }
    http_response_code(401);
    respondHtml('Log masuk gagal', '<p>E-mel atau kata laluan tidak sah, atau akaun tidak mempunyai akses aktif.</p><p><a href="/login">Cuba lagi</a></p>');
}

if ($path === '/logout' && $method === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        http_response_code(419);
        respondHtml('Permintaan tidak sah', '<p>Sila muat semula halaman dan cuba lagi.</p>');
    }
    Auth::logout();
    header('Location: /login');
    exit;
}

if ($path === '/' && $method === 'GET') {
    if (!Auth::check()) {
        header('Location: /login');
        exit;
    }
    $user = Auth::user() ?? [];
    $token = Csrf::token();
    $body = '<p>Selamat datang, ' . escape((string) ($user['name'] ?? '')) . '.</p>'
        . '<p>Identiti pengguna disahkan. Pemilihan tenant dan kebenaran modul belum tersedia.</p>'
        . '<form method="post" action="/logout"><input type="hidden" name="_token" value="' . escape($token) . '"><button type="submit">Log keluar</button></form>'
        . '<p><a href="/health">Health check</a></p>';
    respondHtml('SPORTSchool', $body);
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'not_found']);
