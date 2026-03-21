# Fix: check-coverage.php — Externalize Hardcoded Coverage Path

## Script

`scripts/check-coverage.php`

## Violations

### 1. Hardcoded coverage directory (lines 20-21)

```php
$coverage_dir = $base_dir . '/var/coverage';
$clover_file  = $coverage_dir . '/clover.xml';
```

## Fix

1. Create `coverage-config.yaml` at the project root (or add to an existing general config):

```yaml
coverage_dir: var/coverage
clover_file: var/coverage/clover.xml
```

2. In the script, load the config:

```php
$config = Yaml::parseFile($base_dir . '/coverage-config.yaml');
$coverage_dir = $base_dir . '/' . $config['coverage_dir'];
$clover_file  = $base_dir . '/' . $config['clover_file'];
```

## Constraints

- Add `require __DIR__ . '/../vendor/autoload.php';` (not currently required — needed for Yaml).
- The PHPUnit invocation and coverage parsing logic are generic — keep as-is.
- Run `./run check:all` to verify no regressions.
