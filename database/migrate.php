<?php
declare(strict_types=1);

// Reject web requests before loading application configuration or database code.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$basePath = dirname(__DIR__);
require $basePath . '/bootstrap/app.php';

use App\Core\Database;

/**
 * Split a MySQL migration into statements.
 *
 * Supports single/double-quoted strings, backtick identifiers, escaped and
 * doubled quote characters, --/# line comments and /* block comments * /.
 * Stored procedures and custom DELIMITER directives are intentionally unsupported.
 *
 * @return list<string>
 */
function splitMigrationSql(string $sql): array
{
    if (preg_match('/^\s*DELIMITER\b/im', $sql) === 1) {
        throw new RuntimeException('Custom DELIMITER directives are not supported.');
    }

    $statements = [];
    $buffer = '';
    $state = 'normal';
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($state === 'line_comment') {
            if ($char === "\n" || $char === "\r") {
                $buffer .= ' ';
                $state = 'normal';
            }
            continue;
        }

        if ($state === 'block_comment') {
            if ($char === '*' && $next === '/') {
                $i++;
                $buffer .= ' ';
                $state = 'normal';
            }
            continue;
        }

        if ($state === 'single' || $state === 'double' || $state === 'backtick') {
            $buffer .= $char;
            $closing = $state === 'single' ? "'" : ($state === 'double' ? '"' : '`');

            if ($char === '\\\\' && $state !== 'backtick' && $i + 1 < $length) {
                $buffer .= $sql[++$i];
                continue;
            }
            if ($char === $closing) {
                if ($next === $closing) {
                    $buffer .= $sql[++$i];
                } else {
                    $state = 'normal';
                }
            }
            continue;
        }

        if ($char === '-' && $next === '-' && ($i + 2 >= $length || ord($sql[$i + 2]) <= 32)) {
            $i++;
            $state = 'line_comment';
            continue;
        }
        if ($char === '#') {
            $state = 'line_comment';
            continue;
        }
        if ($char === '/' && $next === '*') {
            $i++;
            $state = 'block_comment';
            continue;
        }
        if ($char === "'") {
            $state = 'single';
            $buffer .= $char;
            continue;
        }
        if ($char === '"') {
            $state = 'double';
            $buffer .= $char;
            continue;
        }
        if ($char === '`') {
            $state = 'backtick';
            $buffer .= $char;
            continue;
        }
        if ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if ($state === 'single' || $state === 'double' || $state === 'backtick' || $state === 'block_comment') {
        throw new RuntimeException('Migration contains an unterminated quoted value, identifier or block comment.');
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

$pdo = Database::connection();
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL UNIQUE,
    batch INT UNSIGNED NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$migrationDirectory = __DIR__ . '/migrations';
$files = glob($migrationDirectory . '/*.sql') ?: [];
sort($files, SORT_STRING);

$batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM schema_migrations')->fetchColumn();
$check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration = ? LIMIT 1');
$record = $pdo->prepare('INSERT INTO schema_migrations (migration, batch) VALUES (?, ?)');

foreach ($files as $file) {
    if (!is_file($file) || !is_readable($file)) {
        throw new RuntimeException('A migration file is not readable.');
    }

    $name = basename($file);
    if (strlen($name) > 190) {
        throw new RuntimeException('Migration filename exceeds 190 characters.');
    }

    $check->execute([$name]);
    if ($check->fetchColumn() !== false) {
        echo "SKIP {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Cannot read migration: {$name}");
    }

    $statements = splitMigrationSql($sql);
    if ($statements === []) {
        throw new RuntimeException("Migration is empty: {$name}");
    }

    try {
        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
        // Record only after every statement in this migration has succeeded.
        $record->execute([$name, $batch]);
        echo "APPLIED {$name}\n";
    } catch (Throwable $exception) {
        throw new RuntimeException(
            "Migration failed and was not recorded: {$name}. Check database state before retrying.",
            0,
            $exception
        );
    }
}

echo "Migrations complete.\n";
