<?php

declare(strict_types=1);

/**
 * Run a read-only SQL query against the database and print results as JSON.
 *
 * Only SELECT statements are permitted; any other statement is rejected before
 * it reaches the database.
 *
 * Usage: docker compose exec web php scripts/db-query.php "SELECT * FROM users LIMIT 5"
 * Via Composer: ./run db:query -- "<sql>"
 *
 * Exit 0 on success, 1 on error.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\DatabaseConfig;

$sql = $argv[1] ?? '';

if ($sql === '') {
    fwrite(STDERR, "Usage: db:query -- \"<SELECT ...>\"\n");
    exit(1);
}

if (! preg_match('/^\s*SELECT\b/i', $sql)) {
    fwrite(STDERR, "Error: only SELECT statements are permitted.\n");
    exit(1);
}

try {
    $db   = new Database(DatabaseConfig::fromEnv());
    $rows = $db->all($sql);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
