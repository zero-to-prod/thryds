<?php

declare(strict_types=1);

/**
 * Runs all checks concurrently and emits a machine-readable JSON summary.
 *
 * Every check runs regardless of failures, so agents see the full picture.
 * Checks execute in parallel via proc_open + stream_select; output is
 * buffered per-check and flushed as a block on completion.
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
    'test'                   => $base_dir . '/vendor/bin/paratest',
];

$fixes = [
    'check:manifest' => './run sync:manifest',
    'check:style'    => './run fix:style',
    'check:rector'   => './run fix:rector',
];

fwrite(STDERR, "\n╔══════════════════════════════════════╗\n");
fwrite(STDERR, "║            Check: All                ║\n");
fwrite(STDERR, "╚══════════════════════════════════════╝\n");

// ── Launch all checks concurrently ──────────────────────────────

$procs   = []; // name => proc resource
$pipes   = []; // name => [1 => stdout pipe, 2 => stderr pipe]
$buffers = []; // name => accumulated output

foreach ($checks as $name => $command) {
    $proc = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $p);
    stream_set_blocking($p[1], false);
    stream_set_blocking($p[2], false);
    $procs[$name]   = $proc;
    $pipes[$name]    = $p;
    $buffers[$name]  = '';
}

// ── Read output via stream_select until all processes complete ───

$results   = [];
$completed = [];

while (count($completed) < count($checks)) {
    // Build stream arrays for select
    $read = [];
    $stream_map = []; // resource id => [name, fd]

    foreach ($pipes as $name => $p) {
        if (isset($completed[$name])) {
            continue;
        }
        foreach ([1, 2] as $fd) {
            if (is_resource($p[$fd])) {
                $read[] = $p[$fd];
                $stream_map[(int) $p[$fd]] = [$name, $fd];
            }
        }
    }

    if ($read === []) {
        break;
    }

    $write = null;
    $except = null;
    $changed = stream_select($read, $write, $except, 0, 200_000);

    if ($changed > 0) {
        foreach ($read as $stream) {
            [$name, $fd] = $stream_map[(int) $stream];
            $chunk = fread($stream, 8192);
            if ($chunk !== false && $chunk !== '') {
                $buffers[$name] .= $chunk;
            }
        }
    }

    // Check for completed processes
    foreach ($procs as $name => $proc) {
        if (isset($completed[$name])) {
            continue;
        }

        $status = proc_get_status($proc);
        if ($status['running']) {
            continue;
        }

        // Drain remaining output
        foreach ([1, 2] as $fd) {
            if (is_resource($pipes[$name][$fd])) {
                while (($chunk = fread($pipes[$name][$fd], 8192)) !== false && $chunk !== '') {
                    $buffers[$name] .= $chunk;
                }
                fclose($pipes[$name][$fd]);
            }
        }

        $exit_code = $status['exitcode'];
        proc_close($proc);

        // Flush buffered output for this check
        fwrite(STDERR, "\n┌─ $name\n");
        if ($buffers[$name] !== '') {
            fwrite(STDERR, $buffers[$name]);
        }

        // Build result entry
        $result = ['status' => $exit_code === 0 ? 'pass' : 'fail'];

        if ($exit_code !== 0 && isset($fixes[$name])) {
            $result['fix'] = $fixes[$name];
        }

        if ($exit_code !== 0) {
            // Try to parse structured violations from our custom scripts.
            // Fall back to last 20 lines of prose for external tools.
            $parsed = json_decode($buffers[$name], associative: true);
            if (is_array($parsed) && isset($parsed['violations'])) {
                $result['violations'] = $parsed['violations'];
            } else {
                $lines = array_filter(explode("\n", $buffers[$name]), fn(string $l) => trim($l) !== '');
                $result['output'] = implode("\n", array_slice($lines, -20));
            }
        }

        $results[$name] = $result;
        $completed[$name] = true;
    }
}

// Ensure results are in the original check order
$ordered_results = [];
foreach ($checks as $name => $_) {
    $ordered_results[$name] = $results[$name];
}

// ── Summary ──────────────────────────────────────────────────────

$failed  = array_filter($ordered_results, fn(array $r): bool => $r['status'] === 'fail');
$passed  = count($ordered_results) - count($failed);
$overall = $failed === [];

fwrite(STDERR, "\n─ Summary ─────────────────────────────\n\n");

foreach ($ordered_results as $name => $result) {
    $label = $result['status'] === 'pass' ? '[ OK ]' : '[FAIL]';
    fwrite(STDERR, "  $label $name\n");
}

fwrite(STDERR, sprintf("\nResult: %d/%d checks passed\n\n", $passed, count($ordered_results)));

echo json_encode(
    value: ['passed' => $overall, 'checks' => $ordered_results],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($overall ? 0 : 1);
