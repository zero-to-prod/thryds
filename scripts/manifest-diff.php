<?php

declare(strict_types=1);

/**
 * Graph diff engine for comparing manifest (desired) against inventory (actual).
 *
 * Returns a structured diff with four categories:
 * - missing_from_code: entities in manifest but not in inventory
 * - missing_from_manifest: entities in inventory but not in manifest
 * - property_drift: entities in both, but with different property values
 */

/** Expand compact column format to full defaults for stable comparison. */
function expandColumnDefaults(array $column): array
{
    static $defaults = null;
    if ($defaults === null) {
        $config   = \Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/manifest-config.yaml');
        $defaults = $config['column_defaults'];
    }

    return array_merge($defaults, $column);
}

/** Normalize a value for comparison: sort arrays, cast types consistently. */
function normalizeForComparison(mixed $value): mixed
{
    if (is_array($value)) {
        // If sequential array (list), sort for stable comparison
        if (array_is_list($value)) {
            sort($value);

            return $value;
        }
        // Associative array — normalize values recursively but preserve key order
        return array_map(normalizeForComparison(...), $value);
    }

    return $value;
}

/**
 * @param array<string, array<string, mixed>> $desired Parsed from thryds.yaml
 * @param array<string, array<string, mixed>> $actual  Produced by inventory
 *
 * @return array{
 *     missing_from_code: list<array{section: string, name: string, desired: array}>,
 *     missing_from_manifest: list<array{section: string, name: string, actual: array}>,
 *     property_drift: list<array{section: string, name: string, field: string, manifest: mixed, actual: mixed}>,
 *     summary: array{total_drift: int, missing_from_code: int, missing_from_manifest: int, property_drift: int}
 * }
 */
function diffGraphs(array $desired, array $actual): array
{
    $missingFromCode     = [];
    $missingFromManifest = [];
    $propertyDrift       = [];

    $sections = array_unique(array_merge(array_keys($desired), array_keys($actual)));

    foreach ($sections as $section) {
        $desiredEntries = $desired[$section] ?? [];
        $actualEntries  = $actual[$section] ?? [];

        // Entities in manifest but not in code
        foreach ($desiredEntries as $name => $props) {
            if (! isset($actualEntries[$name])) {
                $missingFromCode[] = ['section' => $section, 'name' => $name, 'desired' => $props];
            }
        }

        // Entities in code but not in manifest
        foreach ($actualEntries as $name => $props) {
            if (! isset($desiredEntries[$name])) {
                $missingFromManifest[] = ['section' => $section, 'name' => $name, 'actual' => $props];
            }
        }

        // Entities in both — compare properties
        foreach ($desiredEntries as $name => $desiredProps) {
            if (! isset($actualEntries[$name])) {
                continue;
            }
            $actualProps = $actualEntries[$name];

            // For tables, expand column defaults before comparing
            if ($section === 'tables' && isset($desiredProps['columns']) && isset($actualProps['columns'])) {
                $desiredProps['columns'] = array_map(expandColumnDefaults(...), $desiredProps['columns']);
                $actualProps['columns']  = array_map(expandColumnDefaults(...), $actualProps['columns']);
            }

            // Compare each field present in either side
            $allFields = array_unique(array_merge(array_keys($desiredProps), array_keys($actualProps)));
            foreach ($allFields as $field) {
                $dVal = normalizeForComparison($desiredProps[$field] ?? null);
                $aVal = normalizeForComparison($actualProps[$field] ?? null);

                if ($dVal !== $aVal) {
                    $propertyDrift[] = [
                        'section'  => $section,
                        'name'     => $name,
                        'field'    => $field,
                        'manifest' => $desiredProps[$field] ?? null,
                        'actual'   => $actualProps[$field] ?? null,
                    ];
                }
            }
        }
    }

    $total = count($missingFromCode) + count($missingFromManifest) + count($propertyDrift);

    return [
        'missing_from_code'     => $missingFromCode,
        'missing_from_manifest' => $missingFromManifest,
        'property_drift'        => $propertyDrift,
        'summary'               => [
            'total_drift'          => $total,
            'missing_from_code'    => count($missingFromCode),
            'missing_from_manifest' => count($missingFromManifest),
            'property_drift'       => count($propertyDrift),
        ],
    ];
}
