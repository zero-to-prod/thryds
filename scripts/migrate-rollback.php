<?php

declare(strict_types=1);

/**
 * Rolls back the most recently applied migration.
 *
 * Usage: docker compose exec web php scripts/migrate-rollback.php
 * Via Composer: ./run migrate:rollback
 *
 * Exit 0 on success, 1 on error.
 * JSON summary is printed at the end for machine parsing.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\Migrator;
use ZeroToProd\Thryds\Tables\MigrationsTable;

$Migrator = new Migrator(
    Database: new Database(DatabaseConfig::fromEnv()),
    migrations_dir: __DIR__ . '/../migrations',
    migrations_namespace: 'ZeroToProd\\Thryds\\Migrations\\',
);

$Migrator->ensureTable();

echo "\n=== Rollback ===\n\n";

try {
    $rolled_back = $Migrator->rollback();
} catch (RuntimeException $e) {
    echo "\n  [FAIL] " . $e->getMessage() . "\n\n";
    exit(1);
}

if ($rolled_back === null) {
    echo "  Nothing to roll back.\n";
} else {
    echo '  [ OK ] rolled back ' . $rolled_back[MigrationsTable::id] . ' ' . $rolled_back[MigrationsTable::description] . "\n";
}

echo "\n";
echo json_encode(
    value: ['rolled_back' => $rolled_back],
    flags: JSON_PRETTY_PRINT,
) . "\n\n";

exit(0);
