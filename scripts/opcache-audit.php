<?php

declare(strict_types=1);

/**
 * OPcache optimization audit.
 *
 * Usage: docker compose exec web php /app/scripts/opcache-audit.php
 * Via Composer: ./run opcache
 *
 * Generates HTTP traffic to warm the worker's OPcache, then fetches
 * runtime metrics from the /_opcache/status endpoint (worker process).
 * Config checks use ini_get() (same ini for CLI and worker).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ZeroToProd\Thryds\OpcacheStatus;
use ZeroToProd\Thryds\Routes\Route;

$isDev = (bool) ini_get('opcache.validate_timestamps');
$base_url = 'http://localhost:' . ltrim(getenv('SERVER_NAME') ?: ':80', ':');

// Warm the cache, then measure hit rate from a clean baseline
warmRoutes($base_url, requests_per_route: 5);
$before = fetchWorkerStatus($base_url);
warmRoutes($base_url, requests_per_route: 50);
$after = fetchWorkerStatus($base_url);

$result = opcacheAudit($isDev, $base_url, $after, $before);
echo $result;
exit(str_contains($result, '[FAIL]') ? 1 : 0);

function warmRoutes(string $base_url, int $requests_per_route = 20): void
{
    $routes = array_column(Route::cases(), 'value');

    foreach ($routes as $route) {
        for ($i = 0; $i < $requests_per_route; $i++) {
            @file_get_contents($base_url . $route);
        }
    }
}

function fetchWorkerStatus(string $base_url): array|false
{
    $json = @file_get_contents($base_url . Route::opcache_status->value);
    if ($json === false) {
        return false;
    }

    return json_decode($json, true);
}

function opcacheAudit(bool $isDev, string $base_url, array|false $worker_status, array|false $baseline = false): string
{
    $failures = [];
    $warnings = [];
    $passes = [];
    $devNotes = [];

    // 1. Is OPcache enabled?
    $enabled = (bool) ini_get('opcache.enable');
    if (!$enabled) {
        $failures[] = 'opcache.enable is OFF — nothing is cached';

        return formatReport($failures, $warnings, $passes, $devNotes, $isDev);
    }
    $passes[] = 'opcache.enable is ON';

    // 2. Check worker status endpoint
    if ($worker_status === false) {
        $warnings[] = '/_opcache/status unreachable — runtime metrics unavailable (is the server running?)';
    }

    // 3. Check preloading
    $preload = ini_get('opcache.preload');
    if (empty($preload)) {
        if ($isDev) {
            $devNotes[] = 'opcache.preload is empty — disabled in dev so file changes are picked up without restart';
        } else {
            $failures[] = 'opcache.preload is empty — no scripts are preloaded into shared memory';
        }
    } else {
        $preloadCount = $worker_status[OpcacheStatus::preload_statistics][OpcacheStatus::scripts] ?? [];
        $passes[] = sprintf('opcache.preload is set (%s) — %d scripts preloaded', $preload, count($preloadCount));
    }

    // 4. Check validate_timestamps (should be 0 in production)
    $validateTimestamps = ini_get('opcache.validate_timestamps');
    if ($validateTimestamps === '1') {
        if ($isDev) {
            $devNotes[] = 'opcache.validate_timestamps=1 — enabled in dev so file changes are picked up without restart';
        } else {
            $failures[] = 'opcache.validate_timestamps=1 — PHP stat()s every file on every request to check for changes';
        }
    } else {
        $passes[] = 'opcache.validate_timestamps=0 — no unnecessary file stat() calls';
    }

    // 5. Check JIT
    $jitEnabled = ini_get('opcache.jit');
    if (empty($jitEnabled) || $jitEnabled === '0' || $jitEnabled === 'off' || $jitEnabled === 'disable') {
        $warnings[] = sprintf('opcache.jit=%s — JIT compilation is not enabled', var_export($jitEnabled, true));
    } else {
        $passes[] = sprintf('opcache.jit=%s', $jitEnabled);
    }

    $jitBufferRaw = ini_get('opcache.jit_buffer_size');
    $jitBuffer = parseBytes($jitBufferRaw);
    if ($jitBuffer === 0) {
        $warnings[] = 'opcache.jit_buffer_size=0 — even if JIT is configured, it has no memory allocated';
    } else {
        $passes[] = sprintf('opcache.jit_buffer_size=%dM', $jitBuffer / 1048576);
    }

    // 6. Count all PHP files in the project vs max_accelerated_files
    $appFiles = countPhpFiles('/app/src');
    $vendorFiles = countPhpFiles('/app/vendor');
    $totalFiles = $appFiles + $vendorFiles;
    $maxFiles = (int) ini_get('opcache.max_accelerated_files');

    if ($totalFiles > $maxFiles) {
        $failures[] = sprintf(
            'opcache.max_accelerated_files=%d but project has %d PHP files (%d app + %d vendor) — cache will evict scripts',
            $maxFiles,
            $totalFiles,
            $appFiles,
            $vendorFiles,
        );
    } else {
        $passes[] = sprintf(
            'opcache.max_accelerated_files=%d covers all %d PHP files',
            $maxFiles,
            $totalFiles,
        );
    }

    // Runtime checks — require worker status
    if ($worker_status !== false) {
        // 7. Cache hit rate (delta: after warm-up traffic only)
        $hits = ($worker_status[OpcacheStatus::opcache_statistics][OpcacheStatus::hits] ?? 0) - ($baseline ? ($baseline[OpcacheStatus::opcache_statistics][OpcacheStatus::hits] ?? 0) : 0);
        $misses = ($worker_status[OpcacheStatus::opcache_statistics][OpcacheStatus::misses] ?? 0) - ($baseline ? ($baseline[OpcacheStatus::opcache_statistics][OpcacheStatus::misses] ?? 0) : 0);
        $totalRequests = $hits + $misses;
        if ($totalRequests > 0) {
            $hitRate = ($hits / $totalRequests) * 100;
            if ($hitRate < 95 && !$isDev) {
                $failures[] = sprintf('Cache hit rate: %.1f%% (%d hits / %d misses) — should be >95%%', $hitRate, $hits, $misses);
            } else {
                $passes[] = sprintf('Cache hit rate: %.1f%% (%d hits / %d misses)', $hitRate, $hits, $misses);
            }
        }

        // 8. Cached scripts and preload coverage
        $cachedScripts = $worker_status[OpcacheStatus::opcache_statistics][OpcacheStatus::num_cached_scripts] ?? 0;
        $preloadScripts = count($worker_status[OpcacheStatus::preload_statistics][OpcacheStatus::scripts] ?? []);
        if ($cachedScripts > 0) {
            // Scripts that are expected to not be preloaded:
            // blade cache (generated at runtime), dev vendor bootstraps,
            // $PRELOAD$ internal marker, preload.php itself
            $expected_non_preloaded = countExpectedNonPreloaded($base_url);
            $nonPreloaded = $cachedScripts - $preloadScripts - $expected_non_preloaded;
            if ($nonPreloaded > 0 && !empty($preload)) {
                $warnings[] = sprintf(
                    '%d scripts cached, %d via preload, %d expected runtime — %d unexpected scripts not preloaded (run ./run generate:preload)',
                    $cachedScripts,
                    $preloadScripts,
                    $expected_non_preloaded,
                    $nonPreloaded,
                );
            } else {
                $passes[] = sprintf('%d scripts cached (%d via preload, %d expected runtime)', $cachedScripts, $preloadScripts, $expected_non_preloaded);
            }
        } elseif (!$isDev) {
            $failures[] = '0 scripts cached — OPcache is not caching anything';
        }

        // 9. Memory usage
        $memUsed = $worker_status[OpcacheStatus::memory_usage][OpcacheStatus::used_memory] ?? 0;
        $memFree = $worker_status[OpcacheStatus::memory_usage][OpcacheStatus::free_memory] ?? 0;
        $memWasted = $worker_status[OpcacheStatus::memory_usage][OpcacheStatus::wasted_memory] ?? 0;
        $memTotal = $memUsed + $memFree + $memWasted;
        if ($memTotal > 0) {
            $wastedPct = ($memWasted / $memTotal) * 100;
            $usedPct = ($memUsed / $memTotal) * 100;
            if ($wastedPct > 5) {
                $warnings[] = sprintf('%.1f%% memory wasted (fragmentation) — consider restarting or increasing memory', $wastedPct);
            }
            $passes[] = sprintf('Memory: %.1fM used / %.1fM total (%.1f%% utilized, %.1f%% wasted)', $memUsed / 1048576, $memTotal / 1048576, $usedPct, $wastedPct);
        }
    }

    // 10. save_comments
    $saveComments = ini_get('opcache.save_comments');
    if ($saveComments === '1') {
        $warnings[] = 'opcache.save_comments=1 — annotations are stored in memory. Only needed if your app uses reflection on doc comments at runtime.';
    }

    // 11. enable_file_override
    $fileOverride = ini_get('opcache.enable_file_override');
    if ($fileOverride !== '1') {
        $warnings[] = 'opcache.enable_file_override=0 — file_exists/is_file calls won\'t use OPcache, causing extra stat() calls';
    }

    return formatReport($failures, $warnings, $passes, $devNotes, $isDev);
}

/**
 * Counts scripts that are cached but should not be preloaded:
 * blade cache (runtime-generated), dev vendor bootstraps,
 * $PRELOAD$ marker, preload.php itself.
 */
function countExpectedNonPreloaded(string $base_url): int
{
    $json = @file_get_contents($base_url . Route::opcache_scripts->value);
    if ($json === false) {
        return 0;
    }

    $scripts = json_decode($json, true);
    $count = 0;

    foreach ($scripts as $path) {
        if (array_any(\ZeroToProd\Thryds\DevPath::cases(), fn($devPath): bool => str_contains(haystack: $path, needle: $devPath->value))
            || $path === '/app/preload.php'
            || $path === '$PRELOAD$'
        ) {
            $count++;
        }
    }

    return $count;
}

function parseBytes(string $value): int
{
    $value = trim($value);
    $num = (int) $value;
    $suffix = strtoupper(substr($value, -1));

    return match ($suffix) {
        'G' => $num * 1073741824,
        'M' => $num * 1048576,
        'K' => $num * 1024,
        default => $num,
    };
}

function countPhpFiles(string $directory): int
{
    if (!is_dir($directory)) {
        return 0;
    }

    $count = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $count++;
        }
    }

    return $count;
}

function formatReport(array $failures, array $warnings, array $passes, array $devNotes = [], bool $isDev = false): string
{
    $output = "\n=== OPcache Optimization Audit";
    $output .= $isDev ? " (dev mode) ===\n\n" : " ===\n\n";

    if ($failures !== []) {
        $output .= "FAILURES (not optimized):\n";
        foreach ($failures as $f) {
            $output .= "  [FAIL] $f\n";
        }
        $output .= "\n";
    }

    if ($warnings !== []) {
        $output .= "WARNINGS:\n";
        foreach ($warnings as $w) {
            $output .= "  [WARN] $w\n";
        }
        $output .= "\n";
    }

    if ($devNotes !== []) {
        $output .= "DEV MODE (skipped — would fail in production):\n";
        foreach ($devNotes as $d) {
            $output .= "  [DEV ] $d\n";
        }
        $output .= "\n";
    }

    if ($passes !== []) {
        $output .= "PASSING:\n";
        foreach ($passes as $p) {
            $output .= "  [ OK ] $p\n";
        }
        $output .= "\n";
    }

    $total = count($failures) + count($warnings) + count($passes) + count($devNotes);
    $output .= sprintf("Result: %d checks — %d failed, %d warnings, %d dev-skipped, %d passed\n", $total, count($failures), count($warnings), count($devNotes), count($passes));

    if ($failures !== []) {
        $output .= "Verdict: NOT optimized for OPcache\n";
    } elseif ($warnings !== []) {
        $output .= "Verdict: Partially optimized (see warnings)\n";
    } else {
        $output .= "Verdict: Well optimized\n";
    }

    return $output . "\n";
}
