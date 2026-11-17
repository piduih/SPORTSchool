<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use Throwable;
final class HealthController
{
    public function __invoke(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            Database::connection()->query('SELECT 1');
            echo json_encode(['application' => 'SPORTSchool', 'status' => 'ok', 'database' => 'connected']);
        } catch (Throwable $e) {
            http_response_code(503);
            error_log('SPORTSchool health check failed: ' . $e->getMessage());
            echo json_encode(['application' => 'SPORTSchool', 'status' => 'unavailable', 'database' => 'unavailable']);
        }
    }
}
