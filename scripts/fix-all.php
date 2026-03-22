<?php

declare(strict_types=1);

/**
 * Applies all automated fixes then verifies with check:all.
 *
 * With --dry-run (-n), runs only the read-only check equivalents
 * to preview what would be fixed, without mutating any files.
 *
 * Usage: ./run fix:all [--dry-run | -n]
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config  = Yaml::parseFile(__DIR__ . '/fix-all-config.yaml');
$dry_run = array_intersect(['--dry-run', '-n'], $argv ?? []) !== [];
$mode    = $dry_run ? 'check' : 'fix';

$label = $dry_run ? 'Fix: All (dry run)' : 'Fix: All';
$width = 38;
$pad   = (int) floor(($width - strlen($label)) / 2);

fwrite(STDERR, "\n╔" . str_repeat('═', $width) . "╗\n");
fwrite(STDERR, '║' . str_repeat(' ', $pad) . $label . str_repeat(' ', $width - $pad - strlen($label)) . "║\n");
fwrite(STDERR, '╚' . str_repeat('═', $width) . "╝\n\n");

$results = [];
$failed  = false;

foreach ($config['steps'] as $name => $step) {
    $command = $step[$mode];
    fwrite(STDERR, "┌─ $name" . ($dry_run ? ' (check)' : '') . "\n");

    $exit_code = null;
    passthru($command, $exit_code);

    $status          = $exit_code === 0 ? 'pass' : 'fail';
    $results[$name]  = ['status' => $status];

    if ($exit_code !== 0) {
        $failed = true;

        if ($dry_run) {
            $results[$name]['fix'] = $step['fix'];
        }
    }

    fwrite(STDERR, "\n");
}

// Verification step
fwrite(STDERR, "┌─ check:all (verify)\n");

$exit_code = null;
passthru($config['verify'], $exit_code);

if ($exit_code !== 0) {
    $failed = true;
}

$results['check:all'] = ['status' => $exit_code === 0 ? 'pass' : 'fail'];

// Summary
fwrite(STDERR, "\n─ Summary ─────────────────────────────\n\n");

foreach ($results as $name => $result) {
    $label = $result['status'] === 'pass' ? '[ OK ]' : '[FAIL]';
    fwrite(STDERR, "  $label $name\n");
}

$pass_count = count(array_filter($results, fn(array $r): bool => $r['status'] === 'pass'));

fwrite(STDERR, sprintf("\nResult: %d/%d steps passed\n\n", $pass_count, count($results)));

echo json_encode(
    value: ['passed' => !$failed, 'dry_run' => $dry_run, 'steps' => $results],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($failed ? 1 : 0);
