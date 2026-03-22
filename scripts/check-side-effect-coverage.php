<?php

declare(strict_types=1);

/**
 * Verify that every query class with a write attribute has test coverage.
 *
 * Layer 1 (static): each concrete query class must be referenced in a test file.
 * Layer 2 (dynamic): if clover XML exists, trait method execute() call sites must be covered.
 *
 * Usage: ./run check:side-effect-coverage
 * Output: JSON { ok: bool, violations: [...], warnings: [...] }
 * Exit 0 if no violations. Exit 1 if violations found.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$projectRoot = realpath(__DIR__ . '/../') . '/';

// ── Load config ─────────────────────────────────────────────────

$config = Yaml::parseFile(__DIR__ . '/side-effect-coverage-config.yaml');
$cloverFile = $projectRoot . $config['clover_file'];
$writeAttributes = $config['write_attributes'];
$traitMethodMap = $config['trait_method_map'];
$testDirs = array_map(static fn(string $dir): string => $projectRoot . $dir, $config['test_dirs']);

fwrite(STDERR, "\n╔══════════════════════════════════════╗\n");
fwrite(STDERR, "║   Check: Side-Effect Coverage        ║\n");
fwrite(STDERR, "╚══════════════════════════════════════╝\n\n");

// ── Load attribute graph ────────────────────────────────────────

$attrFlags = implode(' ', array_map(
    static fn(string $attr): string => '--attr=' . escapeshellarg($attr),
    $writeAttributes,
));

$graphJson = shell_exec(
    'php ' . escapeshellarg($projectRoot . 'scripts/list-attributes.php')
    . ' --format=json ' . $attrFlags . ' --sections=nodes 2>/dev/null',
);

if ($graphJson === null || $graphJson === '') {
    echo json_encode([
        'ok' => false,
        'violations' => [[
            'rule' => 'graph-load',
            'message' => 'list-attributes.php returned no output',
            'fix' => 'Run ./run list:attributes to diagnose',
        ]],
    ], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$graph = json_decode($graphJson, associative: true);
if (!is_array($graph) || !isset($graph['nodes'])) {
    echo json_encode([
        'ok' => false,
        'violations' => [[
            'rule' => 'graph-parse',
            'message' => 'list-attributes.php returned invalid JSON',
            'fix' => 'Run ./run list:attributes -- --format=json to diagnose',
        ]],
    ], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$nodes = $graph['nodes'];

// ── Identify write query classes ────────────────────────────────

/** @var array<string, array{short_name: string, file: string, attributes: list<string>, trait: string|null, methods: list<string>}> */
$writeQueries = [];

foreach ($nodes as $fqcn => $node) {
    $attrs = $node['attributes'] ?? [];
    $matchedAttrs = [];
    foreach ($writeAttributes as $wa) {
        if (isset($attrs[$wa])) {
            $matchedAttrs[] = $wa;
        }
    }

    if ($matchedAttrs === []) {
        continue;
    }

    $parts = explode('\\', $fqcn);
    $shortName = end($parts);
    $filePath = $projectRoot . $node['file'];

    // Determine which trait the class uses
    $trait = null;
    $methods = [];
    if (is_file($filePath)) {
        $contents = file_get_contents($filePath);
        foreach ($traitMethodMap as $traitName => $traitMethods) {
            if (preg_match('/\buse\s+' . preg_quote($traitName, '/') . '\b/', $contents)) {
                $trait = $traitName;
                $methods = $traitMethods;
                break;
            }
        }
    }

    $writeQueries[$fqcn] = [
        'short_name' => $shortName,
        'file' => $node['file'],
        'attributes' => $matchedAttrs,
        'trait' => $trait,
        'methods' => $methods,
    ];
}

if ($writeQueries === []) {
    echo json_encode(['ok' => true, 'violations' => [], 'warnings' => []], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

fwrite(STDERR, sprintf("Found %d write query classes\n", count($writeQueries)));

// ── Collect test file contents ──────────────────────────────────

/** @var array<string, string> path => contents */
$testFiles = [];
foreach ($testDirs as $testDir) {
    if (!is_dir($testDir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
            $testFiles[$file->getPathname()] = file_get_contents($file->getPathname());
        }
    }
}

// ── Static reference check ──────────────────────────────────────

$violations = [];
$warnings = [];

foreach ($writeQueries as $fqcn => $info) {
    $found = false;
    foreach ($testFiles as $testContent) {
        if (str_contains($testContent, $info['short_name'])
            || str_contains($testContent, $fqcn)) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        $attrLabel = implode(', ', $info['attributes']);
        $methodLabel = $info['methods'] !== [] ? implode('/', $info['methods']) : 'unknown';
        $violations[] = [
            'class' => $info['short_name'],
            'file' => $info['file'],
            'attribute' => $attrLabel,
            'check' => 'static_reference',
            'message' => sprintf(
                '%s is not referenced in any test file',
                $info['short_name'],
            ),
            'fix' => sprintf(
                'Add a database test for %s::%s() in tests/Database/',
                $info['short_name'],
                $info['methods'][0] ?? 'method',
            ),
        ];
        fwrite(STDERR, sprintf("  FAIL  %s — not referenced in tests\n", $info['short_name']));
    } else {
        fwrite(STDERR, sprintf("  PASS  %s — referenced in tests\n", $info['short_name']));
    }
}

// ── Dynamic coverage check (clover XML) ─────────────────────────

if (!is_file($cloverFile)) {
    $warnings[] = sprintf(
        'Clover XML not found at %s — skipping dynamic coverage check. Run ./run check:coverage first.',
        $config['clover_file'],
    );
    fwrite(STDERR, "\n  WARN  Clover XML not found — skipping branch coverage check\n");
} else {
    $xml = new SimpleXMLElement(file_get_contents($cloverFile));

    // Build file coverage index: normalized path → array of line coverage data
    /** @var array<string, array<int, int>> file path suffix → [line_num => count] */
    $fileCoverage = [];
    foreach ($xml->project->package as $package) {
        foreach ($package->file as $file) {
            $path = (string) $file['name'];
            $lines = [];
            foreach ($file->line as $line) {
                if ((string) $line['type'] === 'stmt' || (string) $line['type'] === 'method') {
                    $lines[(int) $line['num']] = (int) $line['count'];
                }
            }
            // Normalize: strip /app/ prefix to match project-relative paths
            $normalized = preg_replace('#^/app/#', '', $path);
            $fileCoverage[$normalized] = $lines;
        }
    }

    // For each trait, find execute() call site lines via source code
    /** @var array<string, array{method: string, line: int}> trait name → execute call sites */
    $traitCallSites = [];
    foreach ($traitMethodMap as $traitName => $traitMethods) {
        $traitFile = $projectRoot . 'src/Queries/' . $traitName . '.php';
        if (!is_file($traitFile)) {
            continue;
        }

        $traitLines = file($traitFile);
        $currentMethod = null;

        foreach ($traitLines as $lineIndex => $lineContent) {
            $lineNum = $lineIndex + 1;

            // Track current method
            if (preg_match('/\bfunction\s+(\w+)\s*\(/', $lineContent, $m)) {
                $currentMethod = $m[1];
            }

            // Detect execute() call sites
            if (str_contains($lineContent, '->execute(') && $currentMethod !== null) {
                $traitCallSites[$traitName][] = [
                    'method' => $currentMethod,
                    'line' => $lineNum,
                ];
            }
        }
    }

    // Check coverage for each write query class's trait methods
    foreach ($writeQueries as $fqcn => $info) {
        if ($info['trait'] === null) {
            continue;
        }

        $traitRelPath = 'src/Queries/' . $info['trait'] . '.php';
        $coverage = $fileCoverage[$traitRelPath] ?? null;
        if ($coverage === null) {
            $warnings[] = sprintf('No coverage data for trait %s', $info['trait']);
            continue;
        }

        $callSites = $traitCallSites[$info['trait']] ?? [];
        foreach ($callSites as $site) {
            if (!in_array($site['method'], $info['methods'], true)) {
                continue;
            }

            $count = $coverage[$site['line']] ?? null;
            if ($count === null || $count === 0) {
                $violations[] = [
                    'class' => $info['short_name'],
                    'file' => $info['file'],
                    'trait' => $info['trait'],
                    'method' => $site['method'],
                    'line' => $site['line'],
                    'check' => 'branch_coverage',
                    'message' => sprintf(
                        '%s::%s() execute() call on line %d has no coverage',
                        $info['trait'],
                        $site['method'],
                        $site['line'],
                    ),
                    'fix' => sprintf(
                        'Add a test that exercises %s::%s()',
                        $info['short_name'],
                        $site['method'],
                    ),
                ];
                fwrite(STDERR, sprintf(
                    "  FAIL  %s::%s() — line %d uncovered\n",
                    $info['trait'],
                    $site['method'],
                    $site['line'],
                ));
            }
        }
    }
}

// ── Output ──────────────────────────────────────────────────────

$ok = $violations === [];

$result = ['ok' => $ok, 'violations' => $violations];
if ($warnings !== []) {
    $result['warnings'] = $warnings;
}

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

exit($ok ? 0 : 1);
