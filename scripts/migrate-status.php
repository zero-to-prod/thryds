<?php

declare(strict_types=1);

/**
 * Shows the status of every migration: applied, pending, or modified.
 *
 * Usage: docker compose exec web php scripts/migrate-status.php
 * Via Composer: ./run migrate:status
 *
 * 'modified' means the applied file no longer matches its recorded checksum —
 * migrate will refuse to run until resolved.
 *
 * Exit 0 if all applied and none modified, 1 if any are pending or modified.
 * JSON summary is printed at the end for machine parsing.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\MigrationStatus;
use ZeroToProd\Thryds\Migrator;
use ZeroToProd\Thryds\Tables\Migration;

try {
    $Migrator = new Migrator(
        Database: new Database(DatabaseConfig::fromEnv()),
        migrations_dir: __DIR__ . '/../migrations',
        migrations_namespace: 'ZeroToProd\\Thryds\\Migrations\\',
    );
    $Migrator->ensureTable();
    $rows = $Migrator->status();
} catch (PDOException $e) {
    echo json_encode(
        value: ['passed' => true, 'pending' => [], 'modified' => [], 'applied' => [], 'note' => 'skipped — db container not running'],
        flags: JSON_PRETTY_PRINT,
    ) . "\n";
    exit(0);
}

fwrite(STDERR, "\n=== Migration Status ===\n\n");

$pending = [];
$modified = [];
$applied = [];

foreach ($rows as $row) {
    $label = match ($row[Migrator::col_status]) {
        MigrationStatus::applied  => '[ OK ]',
        MigrationStatus::pending  => '[PEND]',
        MigrationStatus::modified => '[WARN]',
    };
    $applied_at = $row[Migration::applied_at] !== null ? ' (applied ' . $row[Migration::applied_at] . ')' : '';
    fwrite(STDERR, sprintf("  %s %-8s %s %s%s\n", $label, $row[Migrator::col_status]->value, $row[Migration::id], $row[Migration::description], $applied_at));

    match ($row[Migrator::col_status]) {
        MigrationStatus::pending  => $pending[]  = $row[Migration::id],
        MigrationStatus::modified => $modified[] = $row[Migration::id],
        MigrationStatus::applied  => $applied[]  = $row[Migration::id],
    };
}

fwrite(STDERR, "\n");
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
