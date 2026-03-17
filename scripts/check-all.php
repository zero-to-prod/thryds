<?php

declare(strict_types=1);

/**
 * Runs all checks and emits a machine-readable JSON summary.
 *
 * Unlike Composer's check:all script array, this does not abort on the first
 * failure — every check runs regardless, so agents see the full picture.
 *
 * Exit code: 0 if all checks pass, 1 if any fail.
 * JSON summary is printed at the end for machine parsing.
 *
 * Usage: ./run check:all
 */

$base_dir = dirname(__DIR__);

$checks = [
    'fix:style'              => $base_dir . '/vendor/bin/php-cs-fixer fix',
    'fix:rector'             => $base_dir . '/vendor/bin/rector process',
    'check:types'            => $base_dir . '/vendor/bin/phpstan analyse',
    'check:blade-routes'     => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-routes.php'),
    'check:blade-components' => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-components.php'),
    'check:blade-templates'  => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-templates.php'),
    'test'                   => $base_dir . '/vendor/bin/phpunit',
    'generate:preload'       => 'php ' . escapeshellarg($base_dir . '/scripts/generate-preload.php'),
];

echo "\n╔══════════════════════════════════════╗\n";
echo "║            Check: All               ║\n";
echo "╚══════════════════════════════════════╝\n";

$results = [];

foreach ($checks as $name => $command) {
    echo "\n┌─ $name\n";
    passthru($command, $exit_code);
    $results[$name] = $exit_code === 0 ? 'pass' : 'fail';
}

// ── Summary ──────────────────────────────────────────────────────

$failed  = array_filter($results, fn(string $r): bool => $r === 'fail');
$passed  = count($results) - count($failed);
$overall = $failed === [];

echo "\n┌─ Summary ─────────────────────────────\n\n";

foreach ($results as $name => $result) {
    $label = $result === 'pass' ? '[ OK ]' : '[FAIL]';
    echo "  $label $name\n";
}

echo sprintf("\nResult: %d/%d checks passed\n\n", $passed, count($results));

echo json_encode(
    value: ['passed' => $overall, 'checks' => $results],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($overall ? 0 : 1);
