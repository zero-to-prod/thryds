<?php

declare(strict_types=1);

/**
 * Shows the status of every migration: applied, pending, or modified.
 *
 * Usage: docker compose exec web php scripts/migrate-status.php
 * Via Composer: ./run migrate:status
 *
 * 'modified' means the file was changed after it was applied — this is a
 * checksum mismatch and will block migrate from running.
 *
 * Exit 0 if all applied and none modified, 1 if any are pending or modified.
 * JSON summary is printed at the end for machine parsing.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\MigrationStatus;
use ZeroToProd\Thryds\Migrator;
use ZeroToProd\Thryds\Tables\MigrationsTable;

try {
    $Migrator = new Migrator(
        Database: new Database(DatabaseConfig::fromEnv()),
        migrations_dir: __DIR__ . '/../migrations',
    );
    $Migrator->ensureTable();
    $rows = $Migrator->status();
} catch (PDOException $e) {
    echo "\n  [SKIP] db container not running — start it with: docker compose up -d db\n\n";
    exit(0);
}

echo "\n=== Migration Status ===\n\n";

$pending = [];
$modified = [];
$applied = [];

foreach ($rows as $row) {
    $label = match ($row[Migrator::col_status]) {
        MigrationStatus::applied  => '[ OK ]',
        MigrationStatus::pending  => '[PEND]',
        MigrationStatus::modified => '[WARN]',
    };
    $applied_at = $row[MigrationsTable::applied_at] !== null ? ' (applied ' . $row[MigrationsTable::applied_at] . ')' : '';
    echo sprintf("  %s %-8s %s %s%s\n", $label, $row[Migrator::col_status]->value, $row[MigrationsTable::id], $row[MigrationsTable::description], $applied_at);

    match ($row[Migrator::col_status]) {
        MigrationStatus::pending  => $pending[]  = $row[MigrationsTable::id],
        MigrationStatus::modified => $modified[] = $row[MigrationsTable::id],
        MigrationStatus::applied  => $applied[]  = $row[MigrationsTable::id],
    };
}

echo "\n";
echo json_encode(
    value: [
        'passed'   => $pending === [] && $modified === [],
        'pending'  => $pending,
        'modified' => $modified,
        'applied'  => $applied,
    ],
    flags: JSON_PRETTY_PRINT,
) . "\n\n";

exit($pending === [] && $modified === [] ? 0 : 1);
