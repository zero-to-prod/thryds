<?php

declare(strict_types=1);

/**
 * HTTP endpoint profiler — ranks routes by latency.
 *
 * Usage: docker compose exec web php /app/scripts/audit-profile.php [samples]
 * Via Composer: ./run audit:profile
 *
 * Warms the cache, then hits each public route N times (default: 20), computes
 * p50/p95/p99/max, and ranks by p95 to surface the slowest endpoints.
 *
 * Pass a sample count as the first argument to override the default:
 *   ./run audit:profile -- 50
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config     = Yaml::parseFile(__DIR__ . '/audit-config.yaml');
$routeClass = $config['route_class'];

$base_url = 'http://localhost:' . ltrim(getenv('SERVER_NAME') ?: ':80', ':');
$samples  = max(5, (int) ($argv[1] ?? 20));

$publicRoutes = [];
foreach (\ZeroToProd\Thryds\Routes\RouteSource::cases() as $source) {
    foreach (\ZeroToProd\Thryds\Attributes\RouteEnum::of($source)::cases() as $r) {
        if (\ZeroToProd\Thryds\Attributes\Guarded::of($r) === null) {
            $publicRoutes[] = $r;
        }
    }
}

echo "\n=== Endpoint Profiler ===\n";
echo "Base URL: {$base_url}\n";
echo "Samples:  {$samples} per route\n";
echo 'Routes:   ' . count($publicRoutes) . "\n\n";

// Warm the cache before measuring, then discard those timings.
echo "Warming cache (5 requests/route)...\n";
foreach ($publicRoutes as $route) {
    for ($i = 0; $i < 5; $i++) {
        @file_get_contents($base_url . $route->value);
    }
}

// Profile each route.
$results = [];
foreach ($publicRoutes as $route) {
    echo "  Sampling {$route->value}... ";
    flush();

    $timings = [];
    $errors  = 0;

    for ($i = 0; $i < $samples; $i++) {
        $ch = curl_init($base_url . $route->value);
        if ($ch === false) {
            $errors++;
            continue;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $elapsed  = (float) curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000.0;

        if ($httpCode === 0) {
            $errors++;
            continue;
        }
        $timings[] = $elapsed;
    }

    if ($timings === []) {
        echo "FAILED (server unreachable?)\n";
        continue;
    }

    sort($timings);
    $n = count($timings);

    echo sprintf('p95=%.1fms', percentile($timings, 95));
    if ($errors > 0) {
        echo sprintf(' (%d error(s))', $errors);
    }
    echo "\n";

    $results[$route->value] = [
        'p50'    => percentile($timings, 50),
        'p95'    => percentile($timings, 95),
        'p99'    => percentile($timings, 99),
        'max'    => $timings[$n - 1],
        'min'    => $timings[0],
        'errors' => $errors,
        'count'  => $n,
    ];
}

if ($results === []) {
    echo "\nNo routes could be reached. Is the server running? (./run dev:up)\n\n";
    exit(1);
}

// Sort by p95 descending.
uasort($results, fn(array $a, array $b): int => $b['p95'] <=> $a['p95']);

// Median p95 for hotspot detection.
$p95Values = array_column($results, 'p95');
sort($p95Values);
$medianP95        = percentile($p95Values, 50);
$hotspotThreshold = $medianP95 * 2.0;

// Table.
echo "\nRank  Route" . str_repeat(' ', 22) . "  p50       p95       p99       max       min   Errors\n";
echo str_repeat('─', 84) . "\n";

$rank     = 1;
$hotspots = [];
foreach ($results as $route => $s) {
    $isHotspot = count($results) > 1 && $s['p95'] > $hotspotThreshold;
    if ($isHotspot) {
        $hotspots[] = ['route' => $route, 'p95' => $s['p95'], 'factor' => $s['p95'] / $medianP95];
    }
    $errCell = $s['errors'] > 0 ? sprintf('%d/%d', $s['errors'], $s['errors'] + $s['count']) : '     -';
    printf(
        "  %2d  %-26s %7.1fms %7.1fms %7.1fms %7.1fms %7.1fms  %s%s\n",
        $rank++,
        $route,
        $s['p50'],
        $s['p95'],
        $s['p99'],
        $s['max'],
        $s['min'],
        $errCell,
        $isHotspot ? '  ← HOTSPOT' : '',
    );
}

echo "\n";

if ($hotspots !== []) {
    echo sprintf("Hotspots (p95 > 2x median p95 of %.1fms):\n", $medianP95);
    foreach ($hotspots as $h) {
        echo sprintf("  %s: p95=%.1fms (%.1fx median)\n", $h['route'], $h['p95'], $h['factor']);
    }
} else {
    echo sprintf("No hotspots detected (median p95: %.1fms)\n", $medianP95);
}

echo sprintf(
    "\nSummary: %d route(s) profiled, %d sample(s) each, %d hotspot(s)\n\n",
    count($results),
    $samples,
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
