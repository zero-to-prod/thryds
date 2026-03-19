<?php

declare(strict_types=1);

/**
 * Applies all pending migrations in id order.
 *
 * Usage: docker compose exec web php scripts/migrate.php
 * Via Composer: ./run migrate
 *
 * Throws if any applied migration has a checksum mismatch — edit or restore
 * the file, or run migrate:rollback before re-applying.
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

echo "\n=== Migrate ===\n\n";

try {
    $Migrator->migrate();
} catch (RuntimeException $e) {
    echo "\n  [FAIL] " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "\nDone.\n\n";
exit(0);
