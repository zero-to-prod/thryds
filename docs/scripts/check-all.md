# Fix: check-all.php — Externalize Hardcoded Check Suite

## Script

`scripts/check-all.php`

## Violations

### 1. Hardcoded checks map (lines 24-38)

```php
$checks = [
    'check:manifest'         => 'php ' . escapeshellarg($base_dir . '/scripts/check-manifest.php'),
    'check:composer'         => 'composer validate',
    ...
    'test'                   => $base_dir . '/vendor/bin/paratest',
];
```

Every check name and its command is hardcoded. Adding or removing a check requires editing this script.

### 2. Hardcoded fixes map (lines 40-44)

```php
$fixes = [
    'check:manifest' => './run sync:manifest',
    'check:style'    => './run fix:style',
    'check:rector'   => './run fix:rector',
];
```

## Fix

1. Create `checks-config.yaml` at the project root:

```yaml
checks:
  check:manifest:
    command: "php scripts/check-manifest.php"
    fix: "./run sync:manifest"
  check:composer:
    command: "composer validate"
  check:style:
    command: "vendor/bin/php-cs-fixer fix --dry-run --diff"
    fix: "./run fix:style"
  check:rector:
    command: "vendor/bin/rector process --dry-run"
    fix: "./run fix:rector"
  check:types:
    command: "vendor/bin/phpstan analyse"
  check:migrations:
    command: "php scripts/check-migrations.php"
  check:requirements:
    command: "php scripts/check-requirement-coverage.php"
  check:blade-routes:
    command: "php scripts/lint-blade-routes.php"
  check:blade-components:
    command: "php scripts/lint-blade-components.php"
  check:blade-templates:
    command: "php scripts/lint-blade-templates.php"
  check:blade-push:
    command: "php scripts/check-blade-push.php"
  check:graph:
    command: "php scripts/check-graph.php"
  test:
    command: "vendor/bin/paratest"
```

2. In `check-all.php`, load the config and build `$checks` and `$fixes` dynamically:

```php
$config = Yaml::parseFile($base_dir . '/checks-config.yaml');
$checks = [];
$fixes = [];
foreach ($config['checks'] as $name => $entry) {
    $cmd = $entry['command'];
    // Resolve relative paths against $base_dir
    $checks[$name] = str_starts_with($cmd, 'php ') || str_starts_with($cmd, 'vendor/')
        ? $base_dir . '/' . $cmd
        : $cmd;
    if (isset($entry['fix'])) {
        $fixes[$name] = $entry['fix'];
    }
}
```

3. Add `require __DIR__ . '/../vendor/autoload.php';` for Yaml access (currently not required).

## Constraints

- Preserve the concurrent proc_open execution logic — only the data changes.
- Commands in config should use project-relative paths (no `$base_dir` in YAML).
- The script must resolve relative paths to absolute at runtime.
- Run `./run check:all` to verify no regressions.
