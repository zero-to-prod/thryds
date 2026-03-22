<?php

declare(strict_types=1);

/**
 * Verify that every route with #[Persists] has test coverage for its side-effecting method.
 *
 * Walks the attribute graph: controller with #[Persists] → handlesroute → Route case,
 * then checks that a test exercises the side-effecting HTTP method (e.g., POST) for that route.
 *
 * Static check: integration test file references the route AND calls the side-effecting HTTP method.
 * Dynamic check: if clover XML exists, the controller's persist method has line coverage > 0.
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
$testDir = $projectRoot . $config['test_dir'];

fwrite(STDERR, "\n╔══════════════════════════════════════╗\n");
fwrite(STDERR, "║   Check: Side-Effect Coverage        ║\n");
fwrite(STDERR, "╚══════════════════════════════════════╝\n\n");

// ── Load attribute graph ────────────────────────────────────────

$graphJson = shell_exec(
    'php ' . escapeshellarg($projectRoot . 'scripts/list-attributes.php')
    . ' --format=json --attr=Persists --sections=nodes,edges 2>/dev/null',
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
$edges = $graph['edges'] ?? [];

// ── Build route side-effect map ─────────────────────────────────

/**
 * For each controller with #[Persists], extract:
 * - The Route pattern it handles
 * - The HTTP method that triggers the side effect
 * - The model(s) it persists to
 * - The controller file path
 */

/** @var list<array{controller: string, file: string, route: string, method: string, models: list<string>}> */
$sideEffectRoutes = [];

foreach ($nodes as $fqcn => $node) {
    $attrs = $node['attributes'] ?? [];
    if (!isset($attrs['Persists'])) {
        continue;
    }

    $parts = explode('\\', $fqcn);
    $shortName = end($parts);

    // Extract persisted models
    $persists = $attrs['Persists'];
    $models = [];
    if (is_array($persists)) {
        foreach ($persists as $p) {
            if (isset($p['model'])) {
                $modelParts = explode('\\', $p['model']);
                $models[] = end($modelParts);
            }
        }
    }

    // Find route pattern and side-effecting HTTP method from edges
    $routePattern = null;
    $httpMethod = null;

    foreach ($edges as $edge) {
        if ($edge['from'] !== $shortName) {
            continue;
        }

        if ($edge['rel'] === 'handlesroute' && isset($edge['args']['Route'])) {
            $routePattern = $edge['args']['Route'];
        }

        if ($edge['rel'] === 'handlesmethod' && isset($edge['args']['HttpMethod'])) {
            $httpMethod = $edge['args']['HttpMethod'];
        }
    }

    if ($routePattern === null) {
        continue;
    }

    $sideEffectRoutes[] = [
        'controller' => $shortName,
        'fqcn' => $fqcn,
        'file' => $node['file'],
        'route' => $routePattern,
        'method' => $httpMethod ?? 'POST',
        'models' => $models,
    ];
}

if ($sideEffectRoutes === []) {
    echo json_encode(['ok' => true, 'violations' => [], 'warnings' => []], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

fwrite(STDERR, sprintf("Found %d route(s) with side effects\n", count($sideEffectRoutes)));

// ── Collect test file contents ──────────────────────────────────

/** @var array<string, string> filename => contents */
$testFiles = [];
if (is_dir($testDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
            $testFiles[$file->getFilename()] = file_get_contents($file->getPathname());
        }
    }
}

// ── Static check: route test exercises side-effecting method ────

$violations = [];
$warnings = [];

foreach ($sideEffectRoutes as $route) {
    $methodLower = strtolower($route['method']);
    $modelLabel = implode(', ', $route['models']);

    // Derive Route enum case name from the route pattern (e.g., /register → register)
    $caseName = ltrim($route['route'], '/');
    $caseName = str_replace(['/', '-'], '_', $caseName);

    // Find any test file that references this route
    $hasRouteTest = false;
    $hasMethodTest = false;

    foreach ($testFiles as $testContent) {
        // Check for Route::case_name reference or literal route pattern
        if (!str_contains($testContent, 'Route::' . $caseName)
            && !str_contains($testContent, $route['route'])) {
            continue;
        }

        $hasRouteTest = true;

        // Check for the side-effecting HTTP method call
        if (str_contains($testContent, '$this->' . $methodLower . '(')
            || str_contains($testContent, "method: HttpMethod::{$route['method']}")
            || str_contains($testContent, "HttpMethod::{$route['method']}")) {
            $hasMethodTest = true;
            break;
        }
    }

    if (!$hasRouteTest) {
        $violations[] = [
            'controller' => $route['controller'],
            'route' => $route['route'],
            'method' => $route['method'],
            'models' => $route['models'],
            'check' => 'route_test_missing',
            'message' => sprintf(
                '%s %s has no integration test (persists to %s)',
                $route['method'],
                $route['route'],
                $modelLabel,
            ),
            'fix' => sprintf(
                'Add an integration test for %s %s in tests/Integration/',
                $route['method'],
                $route['route'],
            ),
        ];
        fwrite(STDERR, sprintf(
            "  FAIL  %s %s — no route test\n",
            $route['method'],
            $route['route'],
        ));
    } elseif (!$hasMethodTest) {
        $violations[] = [
            'controller' => $route['controller'],
            'route' => $route['route'],
            'method' => $route['method'],
            'models' => $route['models'],
            'check' => 'method_not_exercised',
            'message' => sprintf(
                'Route %s has a test but does not exercise %s (persists to %s)',
                $route['route'],
                $route['method'],
                $modelLabel,
            ),
            'fix' => sprintf(
                'Add a test that calls $this->%s(Route::...) for %s',
                $methodLower,
                $route['route'],
            ),
        ];
        fwrite(STDERR, sprintf(
            "  FAIL  %s %s — test exists but %s not exercised\n",
            $route['method'],
            $route['route'],
            $route['method'],
        ));
    } else {
        fwrite(STDERR, sprintf(
            "  PASS  %s %s → %s\n",
            $route['method'],
            $route['route'],
            $modelLabel,
        ));
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

    /** @var array<string, array<int, int>> normalized path → [line_num => count] */
    $fileCoverage = [];
    foreach ($xml->project->package as $package) {
        foreach ($package->file as $file) {
            $path = (string) $file['name'];
            $lines = [];
            foreach ($file->line as $line) {
                if ((string) $line['type'] === 'method') {
                    $lines[(string) $line['name']] = (int) $line['count'];
                }
            }
            $normalized = preg_replace('#^/app/#', '', $path);
            $fileCoverage[$normalized] = $lines;
        }
    }

    foreach ($sideEffectRoutes as $route) {
        $controllerCoverage = $fileCoverage[$route['file']] ?? null;
        if ($controllerCoverage === null) {
            $warnings[] = sprintf('No coverage data for %s', $route['controller']);
            continue;
        }

        // Check if the side-effecting method has coverage
        $methodLower = strtolower($route['method']);
        $count = $controllerCoverage[$methodLower] ?? null;

        if ($count === null || $count === 0) {
            $modelLabel = implode(', ', $route['models']);
            $violations[] = [
                'controller' => $route['controller'],
                'route' => $route['route'],
                'method' => $route['method'],
                'check' => 'controller_method_uncovered',
                'message' => sprintf(
                    '%s::%s() has no line coverage (persists to %s)',
                    $route['controller'],
                    $methodLower,
                    $modelLabel,
                ),
                'fix' => sprintf(
                    'Add a test that exercises %s %s end-to-end',
                    $route['method'],
                    $route['route'],
                ),
            ];
            fwrite(STDERR, sprintf(
                "  FAIL  %s::%s() — 0 coverage\n",
                $route['controller'],
                $methodLower,
            ));
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
