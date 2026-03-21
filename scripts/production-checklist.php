<?php

declare(strict_types=1);

/**
 * Production readiness checklist.
 *
 * Runs all verification scripts and reports a combined pass/fail result.
 *
 * Usage: docker compose exec web php /app/scripts/production-checklist.php
 * Via Composer: ./run prod:check
 *
 * Checks:
 *   1. Route caching    — scripts/verify-route-cache.php
 *   2. OPcache          — scripts/opcache-audit.php
 *   3. Template caching — compiled Blade templates are written to disk and reused
 *   4. Component @push  — component templates must use @pushOnce, never bare @push
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Config;

$base_dir = dirname(__DIR__);
$overall_exit = 0;

echo "\n╔══════════════════════════════════════╗\n";
echo "║     Production Readiness Checklist   ║\n";
echo "╚══════════════════════════════════════╝\n";

// ── 1. Route Cache ──────────────────────────────────────────────

echo "\n┌─ 1/4 Route Cache ─────────────────────\n";

$route_exit = runScript('php ' . escapeshellarg(__DIR__ . '/verify-route-cache.php'));
if ($route_exit !== 0) {
    $overall_exit = 1;
}

// ── 2. OPcache ──────────────────────────────────────────────────

echo "┌─ 2/4 OPcache ─────────────────────────\n";

$opcache_exit = runScript('php ' . escapeshellarg(__DIR__ . '/opcache-audit.php'));
if ($opcache_exit !== 0) {
    $overall_exit = 1;
}

// ── 3. Template Cache ───────────────────────────────────────────

echo "┌─ 3/4 Template Cache ──────────────────\n";

$template_exit = verifyTemplateCache($base_dir);
if ($template_exit !== 0) {
    $overall_exit = 1;
}

// ── 4. Component @push directives ───────────────────────────────

echo "┌─ 4/4 Component @push Directives ──────\n";

$push_exit = verifyComponentPushDirectives($base_dir);
if ($push_exit !== 0) {
    $overall_exit = 1;
}

// ── Summary ─────────────────────────────────────────────────────

$checks = [
    ['Route Cache', $route_exit],
    ['OPcache', $opcache_exit],
    ['Template Cache', $template_exit],
    ['Component @push Directives', $push_exit],
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

function verifyComponentPushDirectives(string $base_dir): int
{
    $template_dir = $base_dir . '/templates/components';

    $failures = [];
    $passes = [];

    echo "\n=== Component @push Directive Verification ===\n\n";

    foreach (Component::cases() as $component) {
        $path = $template_dir . '/' . $component->value . '.blade.php';

        if (!file_exists($path)) {
            $failures[] = sprintf('Missing component template: %s', $path);
            continue;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $violations = [];

        foreach ($lines as $line_number => $line) {
            if (preg_match('/@push\(/', $line) || preg_match('/@prepend\(/', $line)) {
                $violations[] = sprintf('line %d: %s', $line_number + 1, trim($line));
            }
        }

        if ($violations !== []) {
            foreach ($violations as $violation) {
                $failures[] = sprintf('Component::%s uses bare @push or @prepend (%s). Use @pushOnce(\'stack\', \'%s\') instead.', $component->name, $violation, $component->value);
            }
        } else {
            $passes[] = sprintf('Component::%s has no bare @push or @prepend', $component->name);
        }
    }

    foreach ($failures as $f) {
        echo "  [FAIL] $f\n";
    }
    foreach ($passes as $p) {
        echo "  [ OK ] $p\n";
    }
    echo "\n";

    $total = count($failures) + count($passes);
    echo sprintf("Result: %d checks — %d failed, %d passed\n", $total, count($failures), count($passes));

    if ($failures !== []) {
        echo "Verdict: Component @push directives are NOT production ready\n\n";

        return 1;
    }

    echo "Verdict: Component @push directives are production ready\n\n";

    return 0;
}

function verifyTemplateCache(string $base_dir): int
{
    $cache_dir = $base_dir . '/var/cache/blade';

    $failures = [];
    $passes = [];

    echo "\n=== Template Cache Verification ===\n\n";

    // 1. Populate the real cache via cache-views.php
    passthru('php ' . escapeshellarg($base_dir . '/scripts/cache-views.php'), $populate_exit);
    if ($populate_exit !== 0) {
        echo "  [FAIL] cache-views.php failed\n\n";

        return 1;
    }

    // 2. Verify the real cache has files for all views and components
    $cached_files = glob($cache_dir . '/*.php') ?: [];
    $expected_min = count(View::cases()) + count(Component::cases());

    if (count($cached_files) >= $expected_min) {
        $passes[] = sprintf('%d compiled files in %s (expected ≥ %d)', count($cached_files), $cache_dir, $expected_min);
    } else {
        $failures[] = sprintf('%d compiled files found in %s, expected ≥ %d', count($cached_files), $cache_dir, $expected_min);
    }

    // 3. Verify second render reuses cache (no recompilation)
    $mtimes = [];
    foreach ($cached_files as $file) {
        $mtimes[$file] = filemtime($file);
    }

    sleep(1);

    $Config = Config::from([
        Config::AppEnv => AppEnv::production->value,
        Config::blade_cache_dir => $cache_dir,
        Config::template_dir => $base_dir . '/templates',
    ]);
    $Vite = new Vite($Config, baseDir: $base_dir, entry_css: [
        Vite::app_entry => [Vite::app_css],
    ]);
    $Blade = App::bootBlade($Config, $Vite);

    foreach (View::cases() as $view) {
        $Blade->make($view->value, $view->stubData())->render();
    }

    $recompiled = 0;
    foreach ($cached_files as $file) {
        if (filemtime($file) !== $mtimes[$file]) {
            $recompiled++;
        }
    }

    if ($recompiled > 0) {
        $failures[] = sprintf('%d files recompiled on second render (expected 0)', $recompiled);
    } else {
        $passes[] = 'Second render reused cached templates (0 recompilations)';
    }

    // Report
    foreach ($failures as $f) {
        echo "  [FAIL] $f\n";
    }
    foreach ($passes as $p) {
        echo "  [ OK ] $p\n";
    }
    echo "\n";

    $total = count($failures) + count($passes);
    echo sprintf("Result: %d checks — %d failed, %d passed\n", $total, count($failures), count($passes));

    if ($failures !== []) {
        echo "Verdict: Template cache is NOT production ready\n\n";

        return 1;
    }

    echo "Verdict: Template cache is production ready\n\n";

    return 0;
}
