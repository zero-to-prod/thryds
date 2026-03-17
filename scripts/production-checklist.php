<?php

declare(strict_types=1);

/**
 * Production readiness checklist.
 *
 * Runs all verification scripts and reports a combined pass/fail result.
 *
 * Usage: docker compose exec web php /app/scripts/production-checklist.php
 * Via Composer: ./run audit:production
 *
 * Checks:
 *   1. Route caching    — scripts/verify-route-cache.php
 *   2. OPcache          — scripts/opcache-audit.php
 *   3. Template caching — compiled Blade templates are written to disk and reused
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

$base_dir = dirname(__DIR__);
$overall_exit = 0;

echo "\n╔══════════════════════════════════════╗\n";
echo "║     Production Readiness Checklist   ║\n";
echo "╚══════════════════════════════════════╝\n";

// ── 1. Route Cache ──────────────────────────────────────────────

echo "\n┌─ 1/3 Route Cache ─────────────────────\n";

$route_exit = runScript('php ' . escapeshellarg(__DIR__ . '/verify-route-cache.php'));
if ($route_exit !== 0) {
    $overall_exit = 1;
}

// ── 2. OPcache ──────────────────────────────────────────────────

echo "┌─ 2/3 OPcache ─────────────────────────\n";

$opcache_exit = runScript('php ' . escapeshellarg(__DIR__ . '/opcache-audit.php'));
if ($opcache_exit !== 0) {
    $overall_exit = 1;
}

// ── 3. Template Cache ───────────────────────────────────────────

echo "┌─ 3/3 Template Cache ──────────────────\n";

$template_exit = verifyTemplateCache($base_dir);
if ($template_exit !== 0) {
    $overall_exit = 1;
}

// ── Summary ─────────────────────────────────────────────────────

$checks = [
    ['Route Cache', $route_exit],
    ['OPcache', $opcache_exit],
    ['Template Cache', $template_exit],
];

echo "┌─ Summary ─────────────────────────────\n\n";

$failed = 0;
foreach ($checks as [$name, $exit_code]) {
    $status = $exit_code === 0 ? '[ OK ]' : '[FAIL]';
    if ($exit_code !== 0) {
        $failed++;
    }
    echo "  $status $name\n";
}

echo sprintf("\nResult: %d/%d checks passed\n", count($checks) - $failed, count($checks));

if ($overall_exit !== 0) {
    echo "Verdict: NOT production ready\n\n";
} else {
    echo "Verdict: Production ready\n\n";
}

exit($overall_exit);

// ─────────────────────────────────────────────────────────────────

function runScript(string $command): int
{
    passthru($command, $exit_code);

    return $exit_code;
}

function verifyTemplateCache(string $base_dir): int
{
    $template_dir = $base_dir . '/templates';
    $cache_dir = $base_dir . '/var/cache/blade-verify-' . uniqid();
    mkdir($cache_dir, 0o755, true);

    $failures = [];
    $passes = [];

    $Config = Config::from([
        Config::AppEnv => AppEnv::development->value,
        Config::blade_cache_dir => $cache_dir,
        Config::template_dir => $template_dir,
    ]);
    $Blade = App::bootBlade($Config, $base_dir);

    $view_data = array_fill_keys(array_column(View::cases(), 'value'), []);
    $view_data[View::error->value] = [
        ErrorViewModel::view_key => ErrorViewModel::from([
            ErrorViewModel::message => 'test',
            ErrorViewModel::status_code => 200,
        ]),
    ];

    // First render — must compile and write cache files
    foreach ($view_data as $view => $data) {
        $Blade->make($view, $data)->render();
    }

    $cached_files = glob($cache_dir . '/*.php');
    if ($cached_files === []) {
        $failures[] = 'No compiled template files found after first render';
    } else {
        $passes[] = sprintf('%d compiled template files created', count($cached_files));
    }

    // Record mtimes
    $mtimes = [];
    foreach ($cached_files as $file) {
        $mtimes[$file] = filemtime($file);
    }

    // Ensure filesystem timestamp granularity
    sleep(1);

    // Second render — cached files must not be recompiled
    foreach ($view_data as $view => $data) {
        $Blade->make($view, $data)->render();
    }

    $recompiled = 0;
    foreach ($cached_files as $file) {
        if (filemtime($file) !== $mtimes[$file]) {
            $recompiled++;
        }
    }

    if ($recompiled > 0) {
        $failures[] = sprintf('%d template files were recompiled on second render (should be 0)', $recompiled);
    } else {
        $passes[] = 'Second render reused all cached templates (no recompilation)';
    }

    // Clean up
    foreach (glob($cache_dir . '/*.php') as $file) {
        unlink($file);
    }
    rmdir($cache_dir);

    // Report
    echo "\n=== Template Cache Verification ===\n\n";

    if ($failures !== []) {
        echo "FAILURES:\n";
        foreach ($failures as $f) {
            echo "  [FAIL] $f\n";
        }
        echo "\n";
    }

    if ($passes !== []) {
        echo "PASSING:\n";
        foreach ($passes as $p) {
            echo "  [ OK ] $p\n";
        }
        echo "\n";
    }

    $total = count($failures) + count($passes);
    echo sprintf("Result: %d checks — %d failed, %d passed\n", $total, count($failures), count($passes));

    if ($failures !== []) {
        echo "Verdict: Template caching is NOT working\n\n";

        return 1;
    }

    echo "Verdict: Template caching is working correctly\n\n";

    return 0;
}
