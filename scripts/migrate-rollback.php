<?php

declare(strict_types=1);

/**
 * Rolls back the most recently applied migration.
 *
 * Usage: docker compose exec web php scripts/migrate-rollback.php
 * Via Composer: ./run migrate:rollback
 *
 * Exit 0 on success, 1 on error.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\Migrator;

$Migrator = new Migrator(
    Database: new Database(DatabaseConfig::fromEnv()),
    migrations_dir: __DIR__ . '/../migrations',
);

$Migrator->ensureTable();

echo "\n=== Rollback ===\n\n";

try {
    $Migrator->rollback();
} catch (RuntimeException $e) {
    echo "\n  [FAIL] " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "\nDone.\n\n";
exit(0);
