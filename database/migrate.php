<?php
declare(strict_types=1);
$basePath = dirname(__DIR__);
require $basePath . '/bootstrap/app.php';
use App\Core\Database;
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only. Do not expose migrations through a web URL.\n");
}
$pdo = Database::connection();
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL UNIQUE,
    batch INT UNSIGNED NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);
$batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM schema_migrations')->fetchColumn();
foreach ($files as $file) {
    $name = basename($file);
    $check = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE migration = ?');
    $check->execute([$name]);
    if ((int) $check->fetchColumn() > 0) {
        echo "SKIP {$name}\n";
        continue;
    }
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Cannot read migration: {$name}");
    }
    $pdo->beginTransaction();
    try {
        $statements = preg_split('/;\\s*(?:\\r?\\n|$)/', $sql) ?: [];
        foreach ($statements as $statement) {
            $statement = preg_replace('/^(?:\\s*--[^\\n]*\\n)+/m', '', trim($statement)) ?? trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
        $insert = $pdo->prepare('INSERT INTO schema_migrations (migration, batch) VALUES (?, ?)');
        $insert->execute([$name, $batch]);
        $pdo->commit();
        echo "APPLIED {$name}\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
echo "Migrations complete.\n";
