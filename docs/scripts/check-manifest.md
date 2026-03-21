# Fix: check-manifest.php — Externalize Hardcoded Manifest Path

## Script

`scripts/check-manifest.php`

## Violation

### 1. Hardcoded manifest file path (line 21)

```php
$manifestPath = $projectRoot . 'thryds.yaml';
```

## Fix

1. Add `manifest_file` to `manifest-config.yaml` (shared with `parse-manifest.php`, `manifest-diff.php`, `build-actual-graph.php`):

```yaml
manifest_file: thryds.yaml
sections:
  - routes
  - controllers
  - views
  - components
  - viewmodels
  - enums
  - tables
  - tests
```

2. In the script, load the config:

```php
$config = Yaml::parseFile($projectRoot . 'manifest-config.yaml');
$manifestPath = $projectRoot . $config['manifest_file'];
```

## Constraints

- The `require` chain for helper scripts (`parse-manifest.php`, `build-actual-graph.php`, `manifest-diff.php`) must still work.
- Run `./run check:all` to verify no regressions.
