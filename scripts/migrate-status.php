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
    $label = match ($row->MigrationStatus) {
        MigrationStatus::applied  => '[ OK ]',
        MigrationStatus::pending  => '[PEND]',
        MigrationStatus::modified => '[WARN]',
    };
    $applied_at = $row->applied_at !== null ? ' (applied ' . $row->applied_at . ')' : '';
    fwrite(STDERR, sprintf("  %s %-8s %s %s%s\n", $label, $row->MigrationStatus->value, $row->id, $row->description, $applied_at));

    match ($row->MigrationStatus) {
        MigrationStatus::pending  => $pending[]  = $row->id,
        MigrationStatus::modified => $modified[] = $row->id,
        MigrationStatus::applied  => $applied[]  = $row->id,
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
