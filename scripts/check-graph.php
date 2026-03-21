<?php

declare(strict_types=1);

/**
 * Enforce declarative graph rules from graph-rules.yaml.
 *
 * Loads the attribute graph via attribute-graph.php --format=json,
 * evaluates discovery/node/edge/layer rules, and reports violations.
 *
 * Usage: ./run check:graph
 * Output: JSON { ok: bool, violations: [{ rule, file?, message, fix }] }
 * Exit 0 if no violations. Exit 1 if violations found.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$projectRoot = realpath(__DIR__ . '/../') . '/';

// ── Load rules ──────────────────────────────────────────────────

$rulesFile = $projectRoot . 'graph-rules.yaml';
if (! is_file($rulesFile)) {
    echo json_encode(['ok' => false, 'violations' => [['rule' => 'config', 'message' => 'graph-rules.yaml not found', 'fix' => 'Create graph-rules.yaml at the project root']]], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$rules = Yaml::parseFile($rulesFile);

// ── Load attribute graph ────────────────────────────────────────

$graphJson = shell_exec('php ' . escapeshellarg($projectRoot . 'scripts/attribute-graph.php') . ' --format=json 2>/dev/null');
if ($graphJson === null || $graphJson === '') {
    echo json_encode(['ok' => false, 'violations' => [['rule' => 'graph-load', 'message' => 'attribute-graph.php returned no output', 'fix' => 'Run ./run list:attributes to diagnose']]], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$graph = json_decode($graphJson, associative: true);
if (! is_array($graph) || ! isset($graph['nodes'])) {
    echo json_encode(['ok' => false, 'violations' => [['rule' => 'graph-parse', 'message' => 'attribute-graph.php returned invalid JSON', 'fix' => 'Run ./run list:attributes -- --format=json to diagnose']]], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$nodes = $graph['nodes'];
$edges = $graph['edges'] ?? [];

// ── Build indexes ───────────────────────────────────────────────

// Set of all node file paths (relative to project root).
$nodeFiles = [];
foreach ($nodes as $entry) {
    $nodeFiles[$entry['file']] = true;
}

// Short name → FQCN map.
$shortToFqcn = [];
foreach ($nodes as $fqcn => $entry) {
    $short = substr(strrchr($fqcn, '\\') ?: ('\\' . $fqcn), 1);
    $shortToFqcn[$short] = $fqcn;
}

// FQCN → short name map.
$fqcnToShort = array_flip($shortToFqcn);

// Enum case values per node short name.
$caseValuesByNode = [];
foreach ($nodes as $fqcn => $entry) {
    $short = $fqcnToShort[$fqcn] ?? null;
    if ($short === null || ! isset($entry['cases'])) {
        continue;
    }
    $values = [];
    foreach ($entry['cases'] as $caseData) {
        if (isset($caseData['value'])) {
            $values[$caseData['value']] = true;
        }
    }
    $caseValuesByNode[$short] = $values;
}

// Incoming edges indexed by target short name.
$incomingEdges = [];
foreach ($edges as $edge) {
    $incomingEdges[$edge['to']][] = $edge;
}

// Outgoing edges indexed by source short name.
$outgoingEdges = [];
foreach ($edges as $edge) {
    $outgoingEdges[$edge['from']][] = $edge;
}

// Node short name → layer map.
$nodeLayerMap = [];
foreach ($nodes as $fqcn => $entry) {
    $short = $fqcnToShort[$fqcn] ?? null;
    if ($short !== null) {
        $nodeLayerMap[$short] = $entry['layer'];
    }
}

$violations = [];

// ── Helpers ─────────────────────────────────────────────────────

/** Recursively find files matching a glob pattern (supports ** for recursive). */
function resolveGlob(string $pattern, string $root): array
{
    // If pattern contains **, use recursive directory iterator.
    if (str_contains($pattern, '**')) {
        $parts = explode('**/', $pattern, 2);
        $baseDir = $root . rtrim($parts[0], '/');
        $filePattern = $parts[1] ?? '*';

        if (! is_dir($baseDir)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($filePattern, $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    // Simple glob.
    $results = glob($root . $pattern);

    return $results !== false ? $results : [];
}

/** Collect all attribute names present anywhere on a node entry. */
function collectAllAttrNames(array $entry): array
{
    $names = array_keys($entry['attributes'] ?? []);
    foreach ($entry['properties'] ?? [] as $propData) {
        $names = [...$names, ...array_keys($propData['attributes'] ?? [])];
    }
    foreach ($entry['methods'] ?? [] as $methodData) {
        $names = [...$names, ...array_keys($methodData['attributes'] ?? [])];
    }
    foreach (['cases', 'constants'] as $section) {
        foreach ($entry[$section] ?? [] as $caseData) {
            $names = [...$names, ...array_keys($caseData['attributes'] ?? [])];
        }
    }

    return array_unique($names);
}

/** Filter graph nodes by a rule's filter spec (layer, attr, kind). */
function filterNodes(array $nodes, array $fqcnToShort, array $filter): array
{
    $result = $nodes;

    if (isset($filter['layer'])) {
        $layers = (array) $filter['layer'];
        $result = array_filter($result, static fn(array $e): bool => in_array($e['layer'], $layers, true));
    }
    if (isset($filter['attr'])) {
        $attrs = (array) $filter['attr'];
        $result = array_filter($result, static function (array $e) use ($attrs): bool {
            $nodeAttrs = collectAllAttrNames($e);
            foreach ($attrs as $wanted) {
                if (in_array($wanted, $nodeAttrs, true)) {
                    return true;
                }
            }

            return false;
        });
    }
    if (isset($filter['kind'])) {
        $kinds = (array) $filter['kind'];
        $result = array_filter($result, static fn(array $e): bool => in_array($e['kind'], $kinds, true));
    }
    if (isset($filter['is_attribute']) && $filter['is_attribute'] === true) {
        $result = array_filter($result, static fn(array $e): bool => ($e['is_attribute'] ?? false) === true);
    }

    return $result;
}

// ── Build derived indexes ───────────────────────────────────────

// All attribute names used across the entire graph (short names).
$allUsedAttrNames = [];
foreach ($nodes as $entry) {
    $allUsedAttrNames = [...$allUsedAttrNames, ...collectAllAttrNames($entry)];
}
$allUsedAttrNames = array_flip(array_unique($allUsedAttrNames));

// ── Evaluate discovery rules ────────────────────────────────────

foreach ($rules['discovery'] ?? [] as $ruleName => $rule) {
    $pattern = $rule['glob'] ?? '';
    if ($pattern === '') {
        continue;
    }

    $excludes = $rule['exclude'] ?? [];
    $strategy = $rule['claimed_by']['strategy'] ?? '';
    $files = resolveGlob($pattern, $projectRoot);

    foreach ($files as $absPath) {
        $relPath = str_replace($projectRoot, '', $absPath);

        // Check excludes.
        $excluded = false;
        foreach ($excludes as $ex) {
            if ($relPath === $ex || fnmatch($ex, $relPath)) {
                $excluded = true;
                break;
            }
        }
        if ($excluded) {
            continue;
        }

        $claimed = false;

        if ($strategy === 'node_file') {
            $claimed = isset($nodeFiles[$relPath]);
        } elseif ($strategy === 'enum_case_value') {
            $nodeName = $rule['claimed_by']['node'] ?? '';
            $stripSuffix = $rule['claimed_by']['strip_suffix'] ?? '';
            $stem = basename($relPath);
            if ($stripSuffix !== '' && str_ends_with($stem, $stripSuffix)) {
                $stem = substr($stem, 0, -strlen($stripSuffix));
            }
            $claimed = isset($caseValuesByNode[$nodeName][$stem]);
        }

        if (! $claimed) {
            $violations[] = [
                'rule' => $ruleName,
                'file' => $relPath,
                'message' => $rule['message'] ?? 'Unclaimed file',
                'fix' => $rule['fix'] ?? '',
            ];
        }
    }
}

// ── Evaluate node rules ─────────────────────────────────────────

foreach ($rules['node'] ?? [] as $ruleName => $rule) {
    $filter = $rule['filter'] ?? [];
    $matching = filterNodes($nodes, $fqcnToShort, $filter);
    $assert = $rule['assert'] ?? '';
    $excludes = array_flip($rule['exclude'] ?? []);

    foreach ($matching as $fqcn => $entry) {
        if (isset($excludes[$entry['file']])) {
            continue;
        }
        $pass = false;

        if ($assert === 'has_attributes') {
            $pass = isset($entry['attributes']) && $entry['attributes'] !== [];
        } elseif ($assert === 'cases_have_attr') {
            $targetAttr = $rule['attr'] ?? '';
            $allAttrs = collectAllAttrNames($entry);
            $pass = in_array($targetAttr, $allAttrs, true);
        } elseif ($assert === 'used_as_attribute') {
            $short = $fqcnToShort[$fqcn] ?? '';
            $pass = isset($allUsedAttrNames[$short]);
        }

        if (! $pass) {
            $violations[] = [
                'rule' => $ruleName,
                'file' => $entry['file'],
                'message' => $rule['message'] ?? 'Node rule violated',
                'fix' => $rule['fix'] ?? '',
            ];
        }
    }
}

// ── Evaluate edge rules ─────────────────────────────────────────

foreach ($rules['edge'] ?? [] as $ruleName => $rule) {
    $filter = $rule['filter'] ?? [];
    $matching = filterNodes($nodes, $fqcnToShort, $filter);
    $assert = $rule['assert'] ?? '';

    foreach ($matching as $fqcn => $entry) {
        $short = $fqcnToShort[$fqcn] ?? '';
        $pass = false;

        if ($assert === 'min_incoming') {
            $rel = $rule['rel'] ?? '';
            $min = $rule['min'] ?? 1;
            $count = 0;
            foreach ($incomingEdges[$short] ?? [] as $edge) {
                if ($rel === '' || $edge['rel'] === $rel) {
                    $count++;
                }
            }
            $pass = $count >= $min;
        } elseif ($assert === 'min_outgoing') {
            $rel = $rule['rel'] ?? '';
            $min = $rule['min'] ?? 1;
            $count = 0;
            foreach ($outgoingEdges[$short] ?? [] as $edge) {
                if ($rel === '' || $edge['rel'] === $rel) {
                    $count++;
                }
            }
            $pass = $count >= $min;
        }

        if (! $pass) {
            $violations[] = [
                'rule' => $ruleName,
                'file' => $entry['file'],
                'message' => $rule['message'] ?? 'Edge rule violated',
                'fix' => $rule['fix'] ?? '',
            ];
        }
    }
}

// ── Evaluate layer rules ────────────────────────────────────────

foreach ($rules['layer'] ?? [] as $ruleName => $rule) {
    $layerName = $rule['layer'] ?? '';
    $assert = $rule['assert'] ?? '';

    if ($assert === 'no_intra_layer_edges') {
        // Collect all short names in this layer.
        $layerNodes = [];
        foreach ($nodeLayerMap as $short => $layer) {
            if ($layer === $layerName) {
                $layerNodes[$short] = true;
            }
        }

        // Check edges where both endpoints are in this layer.
        foreach ($edges as $edge) {
            if (isset($layerNodes[$edge['from']]) && isset($layerNodes[$edge['to']])) {
                $violations[] = [
                    'rule' => $ruleName,
                    'file' => $edge['from_file'] ?? '',
                    'message' => sprintf(
                        '%s — %s has edge to %s (rel: %s)',
                        $rule['message'] ?? 'Intra-layer edge',
                        $edge['from'],
                        $edge['to'],
                        $edge['rel'],
                    ),
                    'fix' => $rule['fix'] ?? '',
                ];
            }
        }
    }
}

// ── Output ──────────────────────────────────────────────────────

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
