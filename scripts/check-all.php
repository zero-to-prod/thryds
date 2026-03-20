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
 * For checks that emit structured JSON (our custom scripts), the summary
 * includes a 'violations' array. For external tools (phpstan, php-cs-fixer,
 * rector, phpunit), it falls back to the last 20 lines of output.
 *
 * Usage: ./run check:all
 */

$base_dir = dirname(__DIR__);

$checks = [
    'check:manifest'         => 'php ' . escapeshellarg($base_dir . '/scripts/check-manifest.php'),
    'check:composer'         => 'composer validate',
    'check:style'            => $base_dir . '/vendor/bin/php-cs-fixer fix --dry-run --diff',
    'check:rector'           => $base_dir . '/vendor/bin/rector process --dry-run',
    'check:types'            => $base_dir . '/vendor/bin/phpstan analyse',
    'check:migrations'       => 'php ' . escapeshellarg($base_dir . '/scripts/check-migrations.php'),
    'check:requirements'     => 'php ' . escapeshellarg($base_dir . '/scripts/check-requirement-coverage.php'),
    'check:blade-routes'     => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-routes.php'),
    'check:blade-components' => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-components.php'),
    'check:blade-templates'  => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-templates.php'),
    'check:blade-push'       => 'php ' . escapeshellarg($base_dir . '/scripts/check-blade-push.php'),
    'test'                   => $base_dir . '/vendor/bin/phpunit',
];

$fixes = [
    'check:manifest' => './run sync:manifest',
    'check:style'    => './run fix:style',
    'check:rector'   => './run fix:rector',
];

fwrite(STDERR, "\n╔══════════════════════════════════════╗\n");
fwrite(STDERR, "║            Check: All                ║\n");
fwrite(STDERR, "╚══════════════════════════════════════╝\n");

$results = [];

foreach ($checks as $name => $command) {
    fwrite(STDERR, "\n┌─ $name\n");

    $proc = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = '';
    while (!feof($pipes[1]) || !feof($pipes[2])) {
        foreach ([1, 2] as $fd) {
            $chunk = fread($pipes[$fd], 4096);
            if ($chunk !== false && $chunk !== '') {
                fwrite(STDERR, $chunk);
                $output .= $chunk;
            }
        }
    }
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit_code = proc_close($proc);

    $result = ['status' => $exit_code === 0 ? 'pass' : 'fail'];

    if ($exit_code !== 0 && isset($fixes[$name])) {
        $result['fix'] = $fixes[$name];
    }

    if ($exit_code !== 0) {
        // Try to parse structured violations from our custom scripts.
        // Fall back to last 20 lines of prose for external tools.
        $parsed = json_decode($output, associative: true);
        if (is_array($parsed) && isset($parsed['violations'])) {
            $result['violations'] = $parsed['violations'];
        } else {
            $lines = array_filter(explode("\n", $output), fn(string $l) => trim($l) !== '');
            $result['output'] = implode("\n", array_slice($lines, -20));
        }
    }

    $results[$name] = $result;
}

// ── Summary ──────────────────────────────────────────────────────

$failed  = array_filter($results, fn(array $r): bool => $r['status'] === 'fail');
$passed  = count($results) - count($failed);
$overall = $failed === [];

fwrite(STDERR, "\n─ Summary ─────────────────────────────\n\n");

foreach ($results as $name => $result) {
    $label = $result['status'] === 'pass' ? '[ OK ]' : '[FAIL]';
    fwrite(STDERR, "  $label $name\n");
}

fwrite(STDERR, sprintf("\nResult: %d/%d checks passed\n\n", $passed, count($results)));

echo json_encode(
    value: ['passed' => $overall, 'checks' => $results],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($overall ? 0 : 1);
