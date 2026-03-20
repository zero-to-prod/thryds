# Phase 5: Graph Diff Engine, check:manifest, sync:manifest

## Objective

Build the diff engine that compares the desired graph (`thryds.yaml`) against the actual graph (inventory via reflection). Create `check:manifest` (report drift, fail if non-zero) and `sync:manifest` (scaffold code for manifest entries missing from code). After this phase, the enforcement loop is operational.

## Prerequisites

- Phase 4 complete: `thryds.yaml` exists, `list:manifest` produces YAML from attributes

## Architecture

```
thryds.yaml ──parse──→ Graph B (desired)
                              │
                        diff(A, B) → DiffResult
                              │
attributes ──inventory──→ Graph A (actual)
```

Both graphs are normalized to the same structure before diffing:

```php
// Normalized graph shape
[
    'routes'      => ['name' => [properties...]],
    'controllers' => ['name' => [properties...]],
    'views'       => ['name' => [properties...]],
    'components'  => ['name' => [properties...]],
    'viewmodels'  => ['name' => [properties...]],
    'enums'       => ['name' => [properties...]],
    'tables'      => ['name' => [properties...]],
    'tests'       => ['name' => [properties...]],
]
```

## Deliverables

### 1. `scripts/manifest-diff.php` — The Diff Engine

A library script (not directly invoked) that provides the diff logic. Used by both `check-manifest.php` and `sync-manifest.php`.

```php
<?php

declare(strict_types=1);

/**
 * Graph diff engine for comparing manifest (desired) against inventory (actual).
 *
 * Returns a structured diff with four categories:
 * - missing_from_code: entities in manifest but not in inventory (need scaffolding)
 * - missing_from_manifest: entities in inventory but not in manifest (need declaration)
 * - property_drift: entities in both, but with different property values
 * - edge_drift: relationships that exist in one graph but not the other
 */
```

**Core function signature:**

```php
/**
 * @param array<string, array<string, mixed>> $desired Parsed from thryds.yaml
 * @param array<string, array<string, mixed>> $actual  Produced by inventory
 * @return array{
 *     missing_from_code: list<array{section: string, name: string, desired: array}>,
 *     missing_from_manifest: list<array{section: string, name: string, actual: array}>,
 *     property_drift: list<array{section: string, name: string, field: string, manifest: mixed, actual: mixed}>,
 *     summary: array{total_drift: int, missing_from_code: int, missing_from_manifest: int, property_drift: int}
 * }
 */
function diffGraphs(array $desired, array $actual): array
```

**Diff logic per section:**

| Section | Properties compared | Notes |
|---|---|---|
| `routes` | `path`, `description`, `dev_only`, `operations`, `controller`, `view` | `controller` and `view` may be absent (null/omitted) — treat missing as null |
| `controllers` | `route`, `operations`, `renders`, `persists`, `redirects_to` | `persists` and `redirects_to` are sorted arrays for stable comparison |
| `views` | `layout`, `title`, `components`, `viewmodels` | `components` and `viewmodels` are sorted arrays |
| `components` | `description`, `props` | Props compared as map of `name → {default, enum}` |
| `viewmodels` | `view_key`, `fields` | Fields compared as map of `name → type` |
| `enums` | `cases` | Sorted array comparison |
| `tables` | `table`, `engine`, `primary_key`, `indexes`, `columns` | Columns compared per-column with compact defaults expansion |
| `tests` | `type`, `covers_routes` | `covers_routes` is sorted array |

**Column comparison detail:**

When comparing table columns, expand compact manifest format to full defaults before diffing:

```php
function expandColumnDefaults(array $column): array
{
    return array_merge([
        'nullable' => false,
        'unsigned' => false,
        'auto_increment' => false,
        'default' => null,
        'precision' => null,
        'scale' => null,
        'values' => null,
        'length' => null,
    ], $column);
}
```

### 2. `scripts/parse-manifest.php` — YAML Parser

Reads `thryds.yaml` and returns the normalized graph structure.

```php
<?php

declare(strict_types=1);

/**
 * Parse thryds.yaml into a normalized graph structure for diffing.
 *
 * Usage: require this file, then call parseManifest($path).
 *
 * @return array<string, array<string, mixed>>
 */
function parseManifest(string $path): array
{
    if (!file_exists($path)) {
        fwrite(STDERR, "Manifest not found: $path\n");
        exit(1);
    }

    $manifest = Yaml::parseFile($path);

    // Normalize: ensure all sections exist, sort arrays for stable comparison
    $sections = ['routes', 'controllers', 'views', 'components', 'viewmodels', 'enums', 'tables', 'tests'];
    foreach ($sections as $section) {
        $manifest[$section] ??= [];
    }

    return $manifest;
}
```

### 3. `scripts/build-actual-graph.php` — Inventory as Normalized Graph

Extracts the attribute graph from inventory output and normalizes it to the same structure as the manifest. This reuses the inventory logic but outputs the normalized array instead of JSON/DOT/YAML.

```php
<?php

declare(strict_types=1);

/**
 * Build the actual graph from PHP attributes via reflection.
 *
 * Returns the same normalized structure as parseManifest() for direct comparison.
 *
 * @return array<string, array<string, mixed>>
 */
function buildActualGraph(string $projectRoot): array
```

This function encapsulates the core reflection logic from `inventory.php` but returns a PHP array instead of printing output. The inventory script can be refactored to call this function internally.

### 4. `scripts/check-manifest.php` — Drift Detection

```php
<?php

declare(strict_types=1);

/**
 * Check thryds.yaml against the attribute graph. Report all drift.
 *
 * Exit 0 if no drift. Exit 1 if any drift found.
 * Outputs structured JSON to stdout for machine consumption.
 * Human-readable summary to stderr.
 *
 * Usage: ./run check:manifest
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/parse-manifest.php';
require __DIR__ . '/build-actual-graph.php';
require __DIR__ . '/manifest-diff.php';

$projectRoot = realpath(__DIR__ . '/../') . '/';
$manifestPath = $projectRoot . 'thryds.yaml';

$desired = parseManifest($manifestPath);
$actual = buildActualGraph($projectRoot);
$diff = diffGraphs($desired, $actual);

// Human-readable stderr output
if ($diff['summary']['total_drift'] === 0) {
    fwrite(STDERR, "Manifest: no drift detected.\n");
} else {
    fwrite(STDERR, sprintf(
        "Manifest drift: %d issue(s) — %d missing from code, %d missing from manifest, %d property mismatches\n",
        $diff['summary']['total_drift'],
        $diff['summary']['missing_from_code'],
        $diff['summary']['missing_from_manifest'],
        $diff['summary']['property_drift'],
    ));

    foreach ($diff['missing_from_code'] as $item) {
        fwrite(STDERR, "  [+code] {$item['section']}/{$item['name']} — declared in manifest, not found in code\n");
    }
    foreach ($diff['missing_from_manifest'] as $item) {
        fwrite(STDERR, "  [+yaml] {$item['section']}/{$item['name']} — found in code, not declared in manifest\n");
    }
    foreach ($diff['property_drift'] as $item) {
        fwrite(STDERR, "  [drift] {$item['section']}/{$item['name']}.{$item['field']} — manifest: " .
            json_encode($item['manifest']) . ", actual: " . json_encode($item['actual']) . "\n");
    }
}

// Machine-readable stdout output
echo json_encode($diff, JSON_PRETTY_PRINT) . "\n";

exit($diff['summary']['total_drift'] === 0 ? 0 : 1);
```

Output format:

```json
{
    "missing_from_code": [
        { "section": "routes", "name": "profile", "desired": {"path": "/profile/{id}", "..."} }
    ],
    "missing_from_manifest": [
        { "section": "components", "name": "avatar", "actual": {"description": "...", "..."} }
    ],
    "property_drift": [
        { "section": "routes", "name": "register", "field": "operations",
          "manifest": {"GET": "...", "POST": "..."}, "actual": {"GET": "..."} }
    ],
    "summary": {
        "total_drift": 3,
        "missing_from_code": 1,
        "missing_from_manifest": 1,
        "property_drift": 1
    }
}
```

### 5. `scripts/sync-manifest.php` — Scaffold Missing Code

```php
<?php

declare(strict_types=1);

/**
 * Scaffold code for entities declared in thryds.yaml but missing from the codebase.
 *
 * Only handles missing_from_code entries. Does NOT modify existing code to fix
 * property_drift — that requires human/agent judgment.
 *
 * Exit 0 on success. Exit 1 on errors.
 *
 * Usage: ./run sync:manifest
 */
```

Sync actions per section:

| Section | What `sync:manifest` creates |
|---|---|
| `routes` | New enum case in `Route.php` with `#[RouteInfo]`, `#[RouteOperation]`, `#[DevOnly]` attributes |
| `controllers` | New controller class file with `#[Persists]`, `#[RedirectsTo]` attributes and stub `__invoke()` |
| `views` | New enum case in `View.php` with `#[ExtendsLayout]`, `#[PageTitle]`, `#[UsesComponent]`, `#[ReceivesViewModel]` attributes. New `templates/{name}.blade.php` stub. |
| `components` | New enum case in `Component.php` with `#[Prop]` attributes. New `templates/components/{name}.blade.php` stub with `@props()`. |
| `viewmodels` | New ViewModel class file with `#[ViewModel]`, `view_key` const, and const+property pairs per field |
| `enums` | New UI enum class file with `#[ClosedSet]` and all declared cases |
| `tables` | New Table class file with `#[Table]`, `#[Column]`, `#[PrimaryKey]` attributes per column |
| `tests` | New test class file extending `IntegrationTestCase` with `#[CoversRoute]` and a stub test method |

After scaffolding, `sync:manifest` calls `generate:routes` (rebuild Route.php, View.php) and `fix:style` (format generated code).

Output format:
```json
{
    "created": [
        { "section": "routes", "name": "profile", "files": ["src/Routes/Route.php (case added)"] },
        { "section": "controllers", "name": "ProfileController", "files": ["src/Controllers/ProfileController.php"] },
        { "section": "views", "name": "profile", "files": ["src/Blade/View.php (case added)", "templates/profile.blade.php"] }
    ],
    "skipped": [],
    "errors": []
}
```

### 6. Composer Scripts

Add to `composer.json` scripts section:

```json
"check:manifest": "php scripts/check-manifest.php",
"sync:manifest": "php scripts/sync-manifest.php"
```

## File Checklist

| File | Action |
|---|---|
| `scripts/manifest-diff.php` | Create — diff engine |
| `scripts/parse-manifest.php` | Create — YAML parser |
| `scripts/build-actual-graph.php` | Create — attribute graph builder |
| `scripts/check-manifest.php` | Create — drift detection command |
| `scripts/sync-manifest.php` | Create — code scaffolding command |
| `composer.json` | Add `check:manifest`, `sync:manifest` scripts |

## Verification

```bash
# With current thryds.yaml matching current code: zero drift
./run check:manifest
# Exit 0, summary shows total_drift: 0

# Add a new route to thryds.yaml that doesn't exist in code
# (temporarily, for testing — remove after)
#   profile:
#     path: /profile/{id}
#     description: User profile
#     operations:
#       GET: View user profile page
#     view: profile

./run check:manifest
# Exit 1, missing_from_code includes route:profile

./run sync:manifest
# Creates Route enum case, View enum case, template stub

./run check:manifest
# Exit 0 — drift resolved

# Clean up test route, then verify
./run check:all
```

## Error Handling

- If `thryds.yaml` doesn't exist: `check:manifest` exits 1 with clear error message
- If `thryds.yaml` has invalid YAML: exits 1 with parse error from `symfony/yaml`
- If `sync:manifest` can't write a file (permissions, invalid path): reports error in JSON, continues with remaining entities
- `sync:manifest` never overwrites existing files — it only creates new ones. Existing file with wrong content is `property_drift`, not `missing_from_code`.
