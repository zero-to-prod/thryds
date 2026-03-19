<?php

declare(strict_types=1);

/**
 * Fails if any migration is pending or has a checksum mismatch.
 *
 * Used by check:all. Run manually via: ./run check:migrations
 *
 * Exit 0 if all applied and no modified files.
 * Exit 1 if any pending or modified migrations exist.
 *
 * Skips gracefully if the db container is not running.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\Migrator;

try {
    $Migrator = new Migrator(
        Database: new Database(DatabaseConfig::fromEnv()),
        migrations_dir: __DIR__ . '/../migrations',
    );
    $Migrator->ensureTable();
    $rows = $Migrator->status();
} catch (PDOException $e) {
    echo "check:migrations skipped — db container not running\n";
    exit(0);
}

$failures = [];

foreach ($rows as $row) {
    if ($row['status'] === 'pending') {
        $failures[] = "[PEND] {$row['id']}: not yet applied — run: ./run migrate";
    }
    if ($row['status'] === 'modified') {
        $failures[] = "[WARN] {$row['id']}: checksum mismatch — file was modified after apply";
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo "$failure\n";
    }
    echo "\ncheck:migrations FAILED\n";
    exit(1);
}

$count = count($rows);
echo "check:migrations OK ($count migration" . ($count === 1 ? '' : 's') . " applied)\n";
exit(0);
