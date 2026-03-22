<?php

declare(strict_types=1);

/**
 * Fails if preload.php is stale (out of sync with what sync:preload would produce).
 *
 * Usage: docker compose exec web php scripts/check-preload.php
 * Via Composer: ./run check:preload
 *
 * Exit 0 if preload.php is up to date. Exit 1 if stale or missing.
 * Output: JSON { ok: bool, violations: [{ rule, message, fix }] }
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config   = Yaml::parseFile(__DIR__ . '/check-preload-config.yaml');
$base_dir = dirname(__DIR__);

$preload_path    = $base_dir . '/' . $config['preload_file'];
$generator_script = $base_dir . '/' . $config['generator_script'];
$tmp_path        = sys_get_temp_dir() . '/preload-check-' . getmypid() . '.php';

$violations = [];

fwrite(STDERR, "Generating preload to temp file for comparison...\n");

$command     = sprintf('php %s --output=%s > /dev/null 2>&1', escapeshellarg($generator_script), escapeshellarg($tmp_path));
$return_code = null;
exec($command, result_code: $return_code);

if ($return_code !== 0 || !file_exists($tmp_path)) {
    $violations[] = [
        'rule'    => 'preload-generation-failed',
        'message' => 'sync-preload.php failed to produce output — cannot check staleness',
        'fix'     => './run sync:preload',
    ];

    echo json_encode(
        value: ['ok' => false, 'violations' => $violations],
        flags: JSON_PRETTY_PRINT,
    ) . "\n";

    exit(1);
}

if (!file_exists($preload_path)) {
    $violations[] = [
        'rule'    => 'preload-missing',
        'message' => $config['preload_file'] . ' does not exist',
        'fix'     => './run sync:preload',
    ];
} elseif (file_get_contents($preload_path) !== file_get_contents($tmp_path)) {
    $violations[] = [
        'rule'    => 'preload-stale',
        'message' => $config['preload_file'] . ' is out of sync with the current codebase',
        'fix'     => './run sync:preload',
    ];
}

@unlink($tmp_path);

fwrite(
    STDERR,
    $violations === []
    ? "Preload: up to date.\n"
    : sprintf("Preload: stale — %d violation(s) found.\n", count($violations))
);

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
