<?php

declare(strict_types=1);

/**
 * Build the actual graph from PHP attributes via reflection.
 *
 * Returns the same normalized structure as parseManifest() for direct comparison.
 * Delegates to inventory.php --format=yaml and parses the output.
 */

use Symfony\Component\Yaml\Yaml;

/**
 * @return array<string, array<string, mixed>>
 */
function buildActualGraph(string $projectRoot): array
{
    $script = $projectRoot . 'scripts/list-inventory.php';
    $yaml   = shell_exec('php ' . escapeshellarg($script) . ' --format=yaml 2>/dev/null');

    if ($yaml === null || $yaml === '') {
        fwrite(STDERR, "Failed to run list-inventory.php\n");
        exit(1);
    }

    $actual = Yaml::parse($yaml);

    $config = Yaml::parseFile(__DIR__ . '/manifest-config.yaml');
    foreach ($config['sections'] as $section) {
        $actual[$section] ??= [];
    }

    return $actual;
}
