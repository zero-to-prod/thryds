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
 * JSON summary is printed at the end for machine parsing.
 */

$base_dir = dirname(__DIR__);

require $base_dir . '/vendor/autoload.php';

use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\Migrator;
use ZeroToProd\Thryds\Tables\Migration;

$Migrator = Migrator::create(
    Database: new Database(DatabaseConfig::fromEnv()),
    base_dir: $base_dir,
);

$Migrator->ensureTable();

echo "\n=== Migrate ===\n\n";

try {
    $applied = $Migrator->migrate();
} catch (RuntimeException $e) {
    echo "\n  [FAIL] " . $e->getMessage() . "\n\n";
    exit(1);
}

foreach ($applied as $migration) {
    echo '  [ OK ] applied ' . $migration[Migration::id] . ' ' . $migration[Migration::description] . "\n";
}

if ($applied === []) {
    echo "  Nothing to apply.\n";
}

echo "\n";
echo json_encode(
    value: ['applied' => $applied, 'total' => count($applied)],
    flags: JSON_PRETTY_PRINT,
) . "\n\n";

exit(0);
