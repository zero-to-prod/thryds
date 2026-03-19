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

echo "\n╔══════════════════════════════════════╗\n";
echo "║            Check: All                ║\n";
echo "╚══════════════════════════════════════╝\n";

$results = [];

foreach ($checks as $name => $command) {
    echo "\n┌─ $name\n";

    $proc = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = '';
    while (!feof($pipes[1]) || !feof($pipes[2])) {
        foreach ([1, 2] as $fd) {
            $chunk = fread($pipes[$fd], 4096);
            if ($chunk !== false && $chunk !== '') {
                echo $chunk;
                $output .= $chunk;
            }
        }
    }
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit_code = proc_close($proc);

    $result = ['status' => $exit_code === 0 ? 'pass' : 'fail'];
    if ($exit_code !== 0) {
        $lines = array_filter(explode("\n", $output), fn(string $l) => trim($l) !== '');
        $result['output'] = implode("\n", array_slice($lines, -20));
    }
    $results[$name] = $result;
}

// ── Summary ──────────────────────────────────────────────────────

$failed  = array_filter($results, fn(array $r): bool => $r['status'] === 'fail');
$passed  = count($results) - count($failed);
$overall = $failed === [];

echo "\n─ Summary ─────────────────────────────\n\n";

foreach ($results as $name => $result) {
    $label = $result['status'] === 'pass' ? '[ OK ]' : '[FAIL]';
    echo "  $label $name\n";
}

echo sprintf("\nResult: %d/%d checks passed\n\n", $passed, count($results));

echo json_encode(
    value: ['passed' => $overall, 'checks' => $results],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($overall ? 0 : 1);
