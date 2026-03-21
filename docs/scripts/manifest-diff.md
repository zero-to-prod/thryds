# Fix: manifest-diff.php — Externalize Hardcoded Sections and Column Defaults

## Script

`scripts/manifest-diff.php`

## Violations

### 1. Hardcoded sections array (line 63)

```php
$sections = ['routes', 'controllers', 'views', 'components', 'viewmodels', 'enums', 'tables', 'tests'];
```

Duplicate of `parse-manifest.php` line 25 and `build-actual-graph.php` line 29.

### 2. Hardcoded column defaults (lines 17-26)

```php
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
```

These defaults mirror the `#[Column]` attribute constructor defaults. If attribute defaults change, this map drifts silently.

## Fix

1. Load `$sections` from `manifest-config.yaml` (shared with `parse-manifest.php` and `build-actual-graph.php`).

2. Add `column_defaults` to `manifest-config.yaml`:

```yaml
column_defaults:
  nullable: false
  unsigned: false
  auto_increment: false
  default: null
  precision: null
  scale: null
  values: null
  length: null
```

3. In `expandColumnDefaults()`, load defaults from config instead of the inline array.

## Constraints

- `manifest-diff.php` does not currently `require` the autoloader — if it needs to load YAML, ensure the autoloader is required via the caller (`check-manifest.php`).
- Do not change the `diffGraphs()` function signature or return shape.
- Run `./run check:all` to verify no regressions.
