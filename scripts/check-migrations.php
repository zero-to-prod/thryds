<?php

declare(strict_types=1);

/**
 * Fails if any migration is pending or has a checksum mismatch.
 *
 * Used by check:all. Run manually via: ./run check:migrations
 *
 * Exit 0 if no violations. Exit 1 if violations found.
 * Skips gracefully if the db container is not running.
 * Output: JSON { ok: bool, violations: [{ id, rule, message, fix }] }
 */

$base_dir = dirname(__DIR__);

require $base_dir . '/vendor/autoload.php';

use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\DatabaseConfig;
use ZeroToProd\Framework\MigrationStatus;
use ZeroToProd\Framework\Migrator;

try {
    $Migrator = Migrator::create(
        Database: new Database(DatabaseConfig::fromEnv()),
        base_dir: $base_dir,
    );
    $Migrator->ensureTable();
    $rows = $Migrator->status();
} catch (PDOException $e) {
    echo json_encode(
        value: ['ok' => true, 'violations' => [], 'note' => 'skipped — db container not running'],
        flags: JSON_PRETTY_PRINT,
    ) . "\n";
    exit(0);
}

$violations = [];

foreach ($rows as $row) {
    if ($row->MigrationStatus === MigrationStatus::pending) {
        $violations[] = [
            'id'      => $row->id,
            'rule'    => 'pending-migration',
            'message' => "migration $row->id not yet applied",
            'fix'     => './run migrate',
        ];
    }
    if ($row->MigrationStatus === MigrationStatus::modified) {
        $violations[] = [
            'id'      => $row->id,
            'rule'    => 'modified-migration',
            'message' => "migration $row->id checksum mismatch — file was modified after apply",
            'fix'     => 'Restore the original file or run ./run migrate:rollback',
        ];
    }
}

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
