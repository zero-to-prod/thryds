<?php

declare(strict_types=1);

/**
 * Parse thryds.yaml into a normalized graph structure for diffing.
 *
 * Usage: require this file, then call parseManifest($path).
 */

use Symfony\Component\Yaml\Yaml;

/**
 * @return array<string, array<string, mixed>>
 */
function parseManifest(string $path): array
{
    if (! file_exists($path)) {
        fwrite(STDERR, "Manifest not found: $path\n");
        exit(1);
    }

    $manifest = Yaml::parseFile($path);

    $sections = ['routes', 'controllers', 'views', 'components', 'viewmodels', 'enums', 'tables', 'tests'];
    foreach ($sections as $section) {
        $manifest[$section] ??= [];
    }

    return $manifest;
}
