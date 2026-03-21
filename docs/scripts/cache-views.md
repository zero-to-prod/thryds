# Fix: cache-views.php — Externalize Hardcoded Vite Entry Config

## Script

`scripts/cache-views.php`

## Violations

### 1. Hardcoded Vite entry CSS config (lines 51-53)

```php
$Vite = new Vite($Config, baseDir: $base_dir, entry_css: [
    Vite::app_entry => [Vite::app_css],
]);
```

### 2. Hardcoded cache and template paths (lines 43-44)

```php
Config::blade_cache_dir => $base_dir . '/var/cache/blade',
Config::template_dir => $base_dir . '/templates',
```

Note: These use `Config` constants but the values are still hardcoded strings.

## Fix

1. Load `blade-config.yaml` (shared with Blade lint scripts):

```php
$config = Yaml::parseFile($base_dir . '/blade-config.yaml');
```

2. Use config values for template and cache directory paths.

3. For Vite entry config, extend `blade-config.yaml` or create a separate `vite-config.yaml`:

```yaml
vite:
  entries:
    app: ["resources/css/app.css"]
```

## Constraints

- This script is `require`d by `generate-preload.php` — ensure config loading doesn't break the include chain.
- The `compileAllTemplates()` function is generic — keep as-is.
- Run `./run check:all` to verify no regressions.
