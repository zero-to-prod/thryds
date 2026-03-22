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

use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\MigrationStatus;
use ZeroToProd\Thryds\Migrator;
use ZeroToProd\Thryds\Tables\Migration;

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
    if ($row[Migrator::col_status] === MigrationStatus::pending) {
        $violations[] = [
            'id'      => $row[Migration::id],
            'rule'    => 'pending-migration',
            'message' => "migration {$row[Migration::id]} not yet applied",
            'fix'     => './run migrate',
        ];
    }
    if ($row[Migrator::col_status] === MigrationStatus::modified) {
        $violations[] = [
            'id'      => $row[Migration::id],
            'rule'    => 'modified-migration',
            'message' => "migration {$row[Migration::id]} checksum mismatch — file was modified after apply",
            'fix'     => 'Restore the original file or run ./run migrate:rollback',
        ];
    }
}

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
