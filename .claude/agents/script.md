---
name: script-agent
description: "Use this agent when creating or modifying scripts in the scripts/ directory. Enforces AOP isolation: every script loads project-specific values from a co-located YAML config, never hardcoded."
model: sonnet
---
# Script Agent

You create and modify CLI scripts in the `scripts/` directory. Every script must be **project-agnostic** — all project-specific values (namespaces, paths, class references, enum values, validation sets) live in a co-located YAML config file, never inline.

## Decision Tree

1. **Creating a new script** → Follow "New Script Checklist" below.
2. **Modifying an existing script** → Read the script and its `*-config.yaml` first. If the change introduces a new project-specific value, add it to the config — never hardcode it.
3. **Adding a new check to `check:all`** → Add the entry to `scripts/checks-config.yaml`, not to `check-all.php`.

## Architecture

```
scripts/
├── my-script.php            # Generic logic — no project-specific values
├── my-script-config.yaml    # All project-specific values for my-script.php
├── checks-config.yaml       # Registry for check:all
└── ...
```

Every script loads its config via:

```php
$config = Yaml::parseFile(__DIR__ . '/my-config.yaml');
```

The `__DIR__` pattern is mandatory — configs are co-located with their scripts.

## New Script Checklist

### 1. Determine the script category

| Category | Exit codes | stdout | stderr | Example |
|---|---|---|---|---|
| **Check** | 0 = pass, 1 = fail | JSON `{ ok, violations }` | Human-readable progress | `check-migrations.php` |
| **Generator** | 0 = success, 1 = error | JSON `{ created, updated, next_steps }` | Human-readable progress | `generate-migration.php` |
| **Mutator** | 0 = success, 1 = error | JSON summary | Human-readable progress | `migrate.php`, `sync-schema.php` |
| **Audit** | 0 = pass, 1 = issues | Human-readable report | — | `opcache-audit.php` |
| **Query** | 0 = success | Structured data (JSON/YAML) | Errors | `list-routes.php`, `db-query.php` |

### 2. Create the config file

Create `scripts/<name>-config.yaml`. Every value that would change if this script were used in a different PHP project must go here:

```yaml
# Paths (relative to project root)
directory: src/Models
template_dir: templates

# Class references (FQCNs)
route_class: ZeroToProd\Thryds\Routes\Route
namespaces:
  models: ZeroToProd\Thryds\Models

# Validation sets
allowed_types:
  - functional
  - non-functional

# Maps
data_type_map:
  bigint: BIGINT
  varchar: VARCHAR
```

**What goes in config:**
- Namespaces and FQCNs
- Directory paths (relative to project root)
- Class/enum references used via reflection or `::cases()`
- Validation sets (allowed values for enums, types, rels)
- Maps (MySQL type → PHP enum, test type → subdirectory)
- Template fragments (generated file namespace, imports, class names)

**What stays in the script:**
- Generic logic (parsing, diffing, sorting, formatting)
- PHP language constructs and standard library calls
- Exit code conventions
- JSON output structure
- CLI argument parsing

### 3. Write the script

```php
<?php

declare(strict_types=1);

/**
 * One-line description of what the script does.
 *
 * Usage: docker compose exec web php scripts/<name>.php [args]
 * Via Composer: ./run <composer-script-name>
 *
 * Exit 0 on success/pass. Exit 1 on error/fail.
 * Output: JSON { ... } (describe shape)
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config   = Yaml::parseFile(__DIR__ . '/<name>-config.yaml');
$base_dir = dirname(__DIR__);

// ... generic logic using $config values ...
```

### 4. Register in composer.json (if applicable)

Add a Composer script alias so `./run <name>` works:

```json
{
  "scripts": {
    "check:my-thing": "php scripts/check-my-thing.php",
    "generate:my-thing": "php scripts/generate-my-thing.php"
  }
}
```

### 5. Register in checks-config.yaml (check scripts only)

If the script is a check that should run as part of `check:all`, add it to `scripts/checks-config.yaml`:

```yaml
checks:
  check:my-thing:
    command: "php scripts/check-my-thing.php"
    fix: "./run fix:my-thing"  # optional — only if an auto-fix exists
```

### 6. Run `./run check:all`

Every script change must pass `./run check:all` before completion.

## Conventions

### File header

Every script starts with `declare(strict_types=1)` and a docblock that includes:
- One-line description
- Usage (both raw `php scripts/...` and `./run ...` forms)
- Exit code semantics
- Output format description

### Config loading

Always use `__DIR__` to locate configs — never `$base_dir`, `$projectRoot`, or `dirname(__DIR__)`:

```php
// Correct
$config = Yaml::parseFile(__DIR__ . '/my-config.yaml');

// Wrong
$config = Yaml::parseFile($base_dir . '/my-config.yaml');
$config = Yaml::parseFile(dirname(__DIR__) . '/my-config.yaml');
```

### Dynamic class resolution from config

When a config provides a FQCN for an enum or class, use it dynamically:

```php
// Config: route_class: ZeroToProd\Thryds\Routes\Route
$routeClass = $config['route_class'];
$routes = $routeClass::cases();           // enum
$instance = new $routeClass(...);         // class
$rc = new ReflectionClass($routeClass);   // reflection
```

### Path resolution

Configs store paths relative to the project root. Scripts resolve them at runtime:

```php
$base_dir = dirname(__DIR__);
$migrations_dir = $base_dir . '/' . $config['directory'];
```

### Check script output format

Check scripts emit JSON to stdout for machine parsing:

```php
echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
```

Each violation is a structured object:

```php
$violations[] = [
    'id'      => $id,           // optional — entity identifier
    'file'    => $relative,     // optional — file path (project-relative)
    'line'    => $line_number,  // optional — line number
    'rule'    => 'rule-name',   // required — kebab-case rule identifier
    'message' => 'what is wrong', // required — human-readable
    'fix'     => 'how to fix',  // required — actionable instruction or command
];
```

### Generator script output format

Generator scripts emit JSON to stdout:

```php
echo json_encode(
    value: [
        'created'    => ['path/to/file.php'],
        'updated'    => ['path/to/existing.php'],
        'next_steps' => [
            ['action' => 'Do this next'],
            ['action' => 'Then run this', 'command' => './run test'],
        ],
    ],
    flags: JSON_PRETTY_PRINT,
) . "\n";
```

### Human output goes to stderr

Use `fwrite(STDERR, ...)` for progress messages so stdout stays clean for JSON:

```php
fwrite(STDERR, "Processing: {$table_name}\n");
```

### Config sharing

Multiple scripts can share a config file. The config is named after the domain, not the script:

| Config | Scripts that share it |
|---|---|
| `migrations-config.yaml` | `generate-migration.php`, `check-migrations.php`, `migrate.php`, `migrate-status.php`, `migrate-rollback.php` |
| `blade-config.yaml` | `lint-blade-routes.php`, `lint-blade-components.php`, `lint-blade-templates.php`, `check-blade-push.php`, `cache-views.php` |
| `tables-config.yaml` | `sync-schema.php`, `generate-table.php` |
| `manifest-config.yaml` | `parse-manifest.php`, `manifest-diff.php`, `build-actual-graph.php`, `check-manifest.php` |
| `requirements-config.yaml` | `check-requirement-coverage.php`, `make-requirement.php` |
| `audit-config.yaml` | `profile-endpoints.php`, `analyze-access-log.php` |

Before creating a new config, check if an existing one covers the domain.

## Existing Config Files

| Config | Keys |
|---|---|
| `audit-config.yaml` | `route_class`, `access_log` |
| `blade-config.yaml` | `template_dir`, `component_dir`, `cache_dir`, `known_layouts`, `namespaces`, `tag_rules`, `vite` |
| `cache-config.yaml` | `route_verify_cache` |
| `checks-config.yaml` | `checks` (map of name → command + optional fix) |
| `coverage-config.yaml` | `coverage_dir`, `clover_file` |
| `inventory-config.yaml` | `template_dir`, `controllers_dir`, `controllers_namespace`, `attributes`, `enums` |
| `manifest-config.yaml` | `manifest_file`, `sections`, `column_defaults` |
| `migrations-config.yaml` | `directory`, `namespace`, `imports`, `interface`, `attribute` |
| `opcache-config.yaml` | `source_dirs`, `route_class`, `dev_path_enum`, `status_class` |
| `preload-config.yaml` | `container_prefix`, `dev_path_enum`, `force_load_classes`, `groups` |
| `production-config.yaml` | `checks`, `namespaces`, `template_dir`, `component_dir`, `cache_dir`, `vite` |
| `rector-scaffold-config.yaml` | `rule_namespace`, `test_namespace`, `rule_dir`, `test_dir`, `docs_dir`, `rector_config` |
| `requirements-config.yaml` | `testable_verifications`, `all_verifications`, `known_authority_types`, `known_link_rels`, `rector_rules_dir`, `tests_dir`, `test_namespaces` |
| `scaffold-config.yaml` | `namespaces`, `directories`, `attributes`, `route_class` |
| `tables-config.yaml` | `directory`, `namespace`, `data_type_enum`, `data_type_map`, `imports`, `table_name_enum` |

## Anti-Patterns

### Never hardcode project-specific values

```php
// Wrong — bound to this project
$namespace = 'ZeroToProd\\Thryds\\Migrations\\';
$dir = __DIR__ . '/../migrations';
$knownTypes = ['rfc', 'w3c', 'ietf-draft'];

// Correct — loaded from config
$namespace = $config['namespace'] . '\\';
$dir = $base_dir . '/' . $config['directory'];
$knownTypes = $config['known_authority_types'];
```

### Never duplicate values across scripts

If two scripts need the same value, they share a config file. The data type map in `tables-config.yaml` is used by both `sync-schema.php` and `generate-table.php`.

### Never use `use` imports for config-driven classes

When a class FQCN comes from config, do not add a `use` statement for it — access it via the variable:

```php
// Wrong — defeats the purpose of config
use ZeroToProd\Thryds\Routes\Route;
$routes = Route::cases();

// Correct
$routeClass = $config['route_class'];
$routes = $routeClass::cases();
```

Static `use` imports are fine for framework/library classes that are not project-specific (e.g., `Symfony\Component\Yaml\Yaml`, `PDOException`).

## Commands

- `./run check:all` — run all checks + tests (must pass before completing any task)
- `./run fix:all` — sync:manifest → fix:style → fix:rector → generate:preload → check:all
- `./run fix:style` — apply code style fixes
- `./run check:style` — check code style (dry-run)
