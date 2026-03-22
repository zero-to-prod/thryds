<?php

declare(strict_types=1);

/**
 * Detects duplicate column constant values within column traits.
 *
 * Each column constant in a *Columns trait must have a unique string value,
 * since two constants mapping to the same SQL column name would silently
 * shadow each other in queries.
 *
 * Used by check:all. Run manually via: ./run check:columns
 *
 * Exit 0 if no violations. Exit 1 if violations found.
 * Output: JSON { ok: bool, violations: [{ file, trait, rule, message, constants }] }
 */

$base_dir = dirname(__DIR__);

require $base_dir . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config = Yaml::parseFile(__DIR__ . '/check-columns-config.yaml');

$directory      = $base_dir . '/' . $config['directory'];
$columnsSuffix  = $config['columns_suffix'];

$violations = [];

foreach (glob($directory . '/*' . $columnsSuffix . '.php') as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        continue;
    }

    // Extract trait name
    if (!preg_match('/\btrait\s+(\w+)/', $contents, $traitMatch)) {
        continue;
    }

    $traitName = $traitMatch[1];

    // Extract all public const string declarations: name = 'value'
    preg_match_all(
        '/public\s+const\s+string\s+(\w+)\s*=\s*[\'"]([^\'"]*)[\'"]/',
        $contents,
        $matches,
        PREG_SET_ORDER,
    );

    // Group constant names by their string value
    $valueMap = [];
    foreach ($matches as $match) {
        $constName  = $match[1];
        $constValue = $match[2];
        $valueMap[$constValue][] = $constName;
    }

    // Flag values declared by more than one constant
    foreach ($valueMap as $value => $constNames) {
        if (count($constNames) <= 1) {
            continue;
        }

        $violations[] = [
            'file'      => str_replace($base_dir . '/', '', $file),
            'trait'     => $traitName,
            'rule'      => 'duplicate-column-value',
            'message'   => sprintf(
                "Column value '%s' declared by multiple constants: %s",
                $value,
                implode(', ', $constNames),
            ),
            'constants' => $constNames,
        ];
    }
}

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
