<?php
declare(strict_types=1);

$basePath = require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/health') {
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

if ($path === '/') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ms"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SPORTSchool</title><body><main><h1>SPORTSchool</h1><p>Platform pengurusan olahraga sekolah.</p><p>M0 foundation is running.</p><p><a href="/health">Health check</a></p></main></body></html>';
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'not_found']);
