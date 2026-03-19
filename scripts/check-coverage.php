<?php

declare(strict_types=1);

/**
 * Runs PHPUnit with code coverage and emits a machine-readable JSON summary.
 *
 * Requires PCOV (installed in the development Docker image).
 * Streams test output to stderr; coverage metrics go to stdout as JSON.
 *
 * Optional first argument: minimum line-coverage threshold (0–100, default 0).
 * Exit code: 0 if tests pass and coverage meets threshold, 1 otherwise.
 *
 * Usage: ./run test:coverage
 *        ./run check:coverage
 *        ./run check:coverage -- 80
 */

$base_dir     = dirname(__DIR__);
$coverage_dir = $base_dir . '/var/coverage';
$clover_file  = $coverage_dir . '/clover.xml';
$threshold    = isset($argv[1]) ? (int) $argv[1] : 0;

if (! is_dir($coverage_dir)) {
    mkdir($coverage_dir, 0755, true);
}

fwrite(STDERR, "\n╔══════════════════════════════════════╗\n");
fwrite(STDERR, "║         Test: Coverage               ║\n");
fwrite(STDERR, "╚══════════════════════════════════════╝\n\n");

$command = $base_dir . '/vendor/bin/phpunit'
    . ' --coverage-text'
    . ' --coverage-clover ' . escapeshellarg($clover_file)
    . ' --colors=never';

$proc   = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
$output = '';

while (! feof($pipes[1]) || ! feof($pipes[2])) {
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
$test_exit = proc_close($proc);

// Parse summary block from PHPUnit coverage text output.
$coverage = [];

foreach ([
    'lines'   => '/Lines:\s+([\d.]+)%\s+\((\d+)\/(\d+)\)/',
    'methods' => '/Methods:\s+([\d.]+)%\s+\((\d+)\/(\d+)\)/',
    'classes' => '/Classes:\s+([\d.]+)%\s+\((\d+)\/(\d+)\)/',
] as $key => $pattern) {
    if (preg_match($pattern, $output, $m)) {
        $coverage[$key] = [
            'pct'     => (float) $m[1],
            'covered' => (int) $m[2],
            'total'   => (int) $m[3],
        ];
    }
}

$lines_pct = $coverage['lines']['pct'] ?? 0.0;
$passed    = $test_exit === 0 && $lines_pct >= $threshold;

$result = [
    'passed'    => $passed,
    'threshold' => $threshold,
    'coverage'  => $coverage,
    'clover'    => $clover_file,
];

if (! $passed) {
    $violations = [];

    if ($test_exit !== 0) {
        $violations[] = 'Tests failed — coverage not enforced until all tests pass.';
    } elseif ($lines_pct < $threshold) {
        $violations[] = sprintf(
            'Line coverage %.2f%% is below the %d%% threshold.',
            $lines_pct,
            $threshold,
        );
    }

    $result['violations'] = $violations;
}

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

exit($passed ? 0 : 1);
