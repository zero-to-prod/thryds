# Fix: parse-manifest.php — Externalize Hardcoded Sections

## Script

`scripts/parse-manifest.php`

## Violation

**Line 25** — Hardcoded manifest sections array:

```php
$sections = ['routes', 'controllers', 'views', 'components', 'viewmodels', 'enums', 'tables', 'tests'];
```

This array is duplicated in `manifest-diff.php` (line 63) and `build-actual-graph.php` (line 29). All three must stay in sync manually.

## Root Cause

The manifest schema is defined inline rather than loaded from a config file. Any new section requires editing three files.

## Fix

1. Create `manifest-config.yaml` at the project root:

```yaml
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

2. In `parse-manifest.php`, replace the hardcoded array with:

```php
$config = Yaml::parseFile(__DIR__ . '/../manifest-config.yaml');
$sections = $config['sections'];
```

3. Apply the same change to `manifest-diff.php` and `build-actual-graph.php`.

## Constraints

- The three files must read from the same config source.
- `parse-manifest.php` already imports `Symfony\Component\Yaml\Yaml`, so no new dependency.
- Do not change the function signatures or return types.
- Run `./run check:all` to verify no regressions.
