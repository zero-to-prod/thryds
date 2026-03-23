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

$base_dir = dirname(__DIR__);

require $base_dir . '/vendor/autoload.php';

use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\DatabaseConfig;
use ZeroToProd\Framework\Migrator;
use ZeroToProd\Framework\Tables\Migration;

$Migrator = Migrator::create(
    Database: new Database(DatabaseConfig::fromEnv()),
    base_dir: $base_dir,
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
    echo '  [ OK ] rolled back ' . $rolled_back[Migration::id] . ' ' . $rolled_back[Migration::description] . "\n";
}

echo "\n";
echo json_encode(
    value: ['rolled_back' => $rolled_back],
    flags: JSON_PRETTY_PRINT,
) . "\n\n";

exit(0);
