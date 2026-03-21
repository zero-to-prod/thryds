# Fix: build-actual-graph.php — Externalize Hardcoded Sections

## Script

`scripts/build-actual-graph.php`

## Violation

**Line 29** — Hardcoded manifest sections array:

```php
$sections = ['routes', 'controllers', 'views', 'components', 'viewmodels', 'enums', 'tables', 'tests'];
```

Third copy of the same array (also in `parse-manifest.php` line 25 and `manifest-diff.php` line 63).

## Fix

1. Load `$sections` from `manifest-config.yaml` (same file used by `parse-manifest.php` and `manifest-diff.php`).

2. Replace the inline array:

```php
$config = Yaml::parseFile($projectRoot . 'manifest-config.yaml');
$sections = $config['sections'];
```

## Constraints

- This file already imports `Symfony\Component\Yaml\Yaml`.
- The `$projectRoot` is not passed directly — the function receives it as a parameter. Derive the config path from `$projectRoot`.
- Do not change the `buildActualGraph()` function signature or return shape.
- Run `./run check:all` to verify no regressions.
