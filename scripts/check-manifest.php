<?php

declare(strict_types=1);

/**
 * Check thryds.yaml against the attribute graph. Report all drift.
 *
 * Exit 0 if no drift. Exit 1 if any drift found.
 * Outputs structured JSON to stdout for machine consumption.
 * Human-readable summary to stderr.
 *
 * Usage: ./run check:manifest
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/parse-manifest.php';
require __DIR__ . '/build-actual-graph.php';
require __DIR__ . '/manifest-diff.php';

$projectRoot  = realpath(__DIR__ . '/../') . '/';
$manifestPath = $projectRoot . 'thryds.yaml';

$desired = parseManifest($manifestPath);
$actual  = buildActualGraph($projectRoot);
$diff    = diffGraphs($desired, $actual);

if ($diff['summary']['total_drift'] === 0) {
    fwrite(STDERR, "Manifest: no drift detected.\n");
} else {
    fwrite(STDERR, sprintf(
        "Manifest drift: %d issue(s) — %d missing from code, %d missing from manifest, %d property mismatches\n",
        $diff['summary']['total_drift'],
        $diff['summary']['missing_from_code'],
        $diff['summary']['missing_from_manifest'],
        $diff['summary']['property_drift'],
    ));

    foreach ($diff['missing_from_code'] as $item) {
        fwrite(STDERR, "  [+code] {$item['section']}/{$item['name']} — declared in manifest, not found in code\n");
    }
    foreach ($diff['missing_from_manifest'] as $item) {
        fwrite(STDERR, "  [+yaml] {$item['section']}/{$item['name']} — found in code, not declared in manifest\n");
    }
    foreach ($diff['property_drift'] as $item) {
        fwrite(STDERR, "  [drift] {$item['section']}/{$item['name']}.{$item['field']} — manifest: "
            . json_encode($item['manifest']) . ', actual: ' . json_encode($item['actual']) . "\n");
    }
}

echo json_encode($diff, JSON_PRETTY_PRINT) . "\n";

exit($diff['summary']['total_drift'] === 0 ? 0 : 1);
