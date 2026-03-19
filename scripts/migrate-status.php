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
use ZeroToProd\Thryds\Migrator;

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
    $label = match ($row['status']) {
        'applied'  => '[ OK ]',
        'pending'  => '[PEND]',
        'modified' => '[WARN]',
        default    => '[????]',
    };
    $applied_at = $row['applied_at'] !== null ? ' (applied ' . $row['applied_at'] . ')' : '';
    echo sprintf("  %s %-8s %s %s%s\n", $label, $row['status'], $row['id'], $row['description'], $applied_at);

    match ($row['status']) {
        'pending'  => $pending[]  = $row['id'],
        'modified' => $modified[] = $row['id'],
        default    => $applied[]  = $row['id'],
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
