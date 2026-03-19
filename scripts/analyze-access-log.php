<?php

declare(strict_types=1);

/**
 * Access log hotspot analyzer — ranks routes by p95 latency from real traffic.
 *
 * Usage: docker compose exec web php /app/scripts/analyze-access-log.php [log-path]
 * Via Composer: ./run audit:hotspots
 *
 * Parses logs/frankenphp/access.log (newline-delimited JSON) and ranks known
 * app routes by p95 latency. Run ./run test:load first to generate meaningful
 * traffic. Pass an alternate log path as the first argument to analyze a
 * different file.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ZeroToProd\Thryds\Routes\Route;

$logPath = $argv[1] ?? dirname(__DIR__) . '/logs/frankenphp/access.log';

echo "\n=== Access Log Hotspot Analysis ===\n";
echo "Log: {$logPath}\n";

if (!file_exists($logPath)) {
    echo "\n[ERROR] Log file not found.\n";
    echo "Start the server (./run dev:up), generate traffic, then retry.\n\n";
    exit(1);
}

// Index of known app routes (all cases, including dev-only) for fast lookup.
/** @var array<string, true> $knownRoutes */
$knownRoutes = array_fill_keys(array_column(Route::cases(), 'value'), true);

// Parse the log line by line to avoid loading the entire file into memory.
/** @var array<string, array{durations: float[], errors: int, total: int}> $byRoute */
$byRoute    = [];
$totalLines = 0;
$firstTs    = '';
$lastTs     = '';

$handle = fopen($logPath, 'r');
if ($handle === false) {
    echo "\n[ERROR] Cannot open log file: {$logPath}\n\n";
    exit(1);
}

while (($line = fgets($handle)) !== false) {
    $entry = json_decode($line, true);
    if (!is_array($entry) || ($entry['msg'] ?? '') !== 'handled request') {
        continue;
    }

    $request = $entry['request'] ?? [];
    if (!is_array($request)) {
        continue;
    }

    $uri      = is_string($request['uri'] ?? null) ? $request['uri'] : '';
    $method   = is_string($request['method'] ?? null) ? $request['method'] : 'GET';
    $status   = is_int($entry['status'] ?? null) ? $entry['status'] : 0;
    $duration = is_float($entry['duration'] ?? null) || is_int($entry['duration'] ?? null)
        ? (float) $entry['duration'] * 1000.0  // seconds → ms
        : 0.0;
    $ts = is_string($entry['ts'] ?? null) ? $entry['ts'] : '';

    // Skip static assets, mercure, and anything outside the known route set.
    if (!isset($knownRoutes[$uri])) {
        continue;
    }

    $totalLines++;

    if ($firstTs === '' || $ts < $firstTs) {
        $firstTs = $ts;
    }
    if ($lastTs === '' || $ts > $lastTs) {
        $lastTs = $ts;
    }

    $key = "{$method} {$uri}";
    if (!isset($byRoute[$key])) {
        $byRoute[$key] = ['durations' => [], 'errors' => 0, 'total' => 0];
    }
    $byRoute[$key]['durations'][] = $duration;
    $byRoute[$key]['total']++;
    if ($status >= 500) {
        $byRoute[$key]['errors']++;
    }
}
fclose($handle);

if ($totalLines === 0) {
    echo "\nNo matching app-route entries found.\n";
    echo "Run ./run test:load to generate traffic, then retry.\n\n";
    exit(0);
}

echo "Period: {$firstTs} → {$lastTs}\n";
echo sprintf("Sample: %d request(s) across %d route(s)\n\n", $totalLines, count($byRoute));

// Compute per-route stats.
/** @var array<string, array{count: int, errors: int, error_pct: float, p50: float, p95: float, p99: float, max: float, min: float}> $results */
$results = [];
foreach ($byRoute as $key => $data) {
    $d = $data['durations'];
    sort($d);
    $n          = count($d);
    $results[$key] = [
        'count'     => $data['total'],
        'errors'    => $data['errors'],
        'error_pct' => $data['total'] > 0 ? ($data['errors'] / $data['total']) * 100.0 : 0.0,
        'p50'       => percentile($d, 50),
        'p95'       => percentile($d, 95),
        'p99'       => percentile($d, 99),
        'max'       => $n > 0 ? $d[$n - 1] : 0.0,
        'min'       => $n > 0 ? $d[0] : 0.0,
    ];
}

// Sort by p95 descending.
uasort($results, fn(array $a, array $b): int => $b['p95'] <=> $a['p95']);

// Median p95 for hotspot detection.
$p95Values = array_column($results, 'p95');
sort($p95Values);
$medianP95        = percentile($p95Values, 50);
$hotspotThreshold = $medianP95 * 2.0;

// Table.
echo 'Rank  Route' . str_repeat(' ', 28) . " Requests    p50       p95       p99       max   Errors\n";
echo str_repeat('─', 90) . "\n";

$rank     = 1;
$hotspots = [];
foreach ($results as $route => $s) {
    $isHotspot = count($results) > 1 && $s['p95'] > $hotspotThreshold;
    if ($isHotspot) {
        $hotspots[] = [
            'route'     => $route,
            'p95'       => $s['p95'],
            'error_pct' => $s['error_pct'],
            'factor'    => $medianP95 > 0.0 ? $s['p95'] / $medianP95 : 0.0,
        ];
    }
    printf(
        "  %2d  %-32s %5d  %7.1fms %7.1fms %7.1fms %7.1fms  %5.1f%%%s\n",
        $rank++,
        $route,
        $s['count'],
        $s['p50'],
        $s['p95'],
        $s['p99'],
        $s['max'],
        $s['error_pct'],
        $isHotspot ? '  ← HOTSPOT' : '',
    );
}

echo "\n";

if ($hotspots !== []) {
    echo sprintf("Hotspots (p95 > 2x median p95 of %.1fms):\n", $medianP95);
    foreach ($hotspots as $h) {
        $errNote = $h['error_pct'] > 0.0 ? sprintf(', %.1f%% errors', $h['error_pct']) : '';
        echo sprintf("  %s: p95=%.1fms (%.1fx median%s)\n", $h['route'], $h['p95'], $h['factor'], $errNote);
    }
} else {
    echo sprintf("No hotspots detected (median p95: %.1fms)\n", $medianP95);
}

echo sprintf(
    "\nSummary: %d route(s), %d total request(s), %d hotspot(s)\n\n",
    count($results),
    $totalLines,
    count($hotspots),
);

// ── Helpers ────────────────────────────────────────────────────────────────

/** Returns the Nth percentile from a pre-sorted array. */
function percentile(array $sorted, float $p): float
{
    $n = count($sorted);
    if ($n === 0) {
        return 0.0;
    }
    if ($n === 1) {
        return (float) $sorted[0];
    }
    $index = ($p / 100.0) * ($n - 1);
    $lower = (int) floor($index);
    $upper = (int) ceil($index);
    if ($lower === $upper) {
        return (float) $sorted[$lower];
    }

    return (float) $sorted[$lower] + ($index - $lower) * ((float) $sorted[$upper] - (float) $sorted[$lower]);
}
