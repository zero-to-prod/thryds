# Fix: verify-route-cache.php — Externalize Hardcoded Cache Path

## Script

`scripts/verify-route-cache.php`

## Violation

### 1. Hardcoded cache file path (line 28)

```php
$cache_file = __DIR__ . '/../var/cache/route-verify.cache';
```

## Fix

1. Create or extend a `cache-config.yaml`:

```yaml
route_verify_cache: var/cache/route-verify.cache
```

2. In the script, load the config:

```php
$config = Yaml::parseFile(__DIR__ . '/../cache-config.yaml');
$cache_file = __DIR__ . '/../' . $config['route_verify_cache'];
```

## Constraints

- The verification logic (CachedRouter, FileCache, dispatch testing) is generic — keep as-is.
- Run `./run check:all` to verify no regressions.
