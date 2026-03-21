# Fix: profile-endpoints.php — Externalize Hardcoded Route Class Reference

## Script

`scripts/profile-endpoints.php`

## Violations

### 1. Hardcoded Route class import (line 20)

```php
use ZeroToProd\Thryds\Routes\Route;
```

### 2. Hardcoded dev-only filter method (line 25)

```php
$publicRoutes = array_values(array_filter(Route::cases(), fn(Route $r) => !$r->isDevOnly()));
```

## Fix

1. Create `audit-config.yaml` at the project root (shared with `analyze-access-log.php`):

```yaml
route_class: ZeroToProd\Thryds\Routes\Route
access_log: logs/frankenphp/access.log
```

2. In the script, load the config:

```php
$config = Yaml::parseFile(__DIR__ . '/../audit-config.yaml');
$routeClass = $config['route_class'];
$publicRoutes = array_values(array_filter($routeClass::cases(), fn($r) => !$r->isDevOnly()));
```

## Constraints

- The Route enum is used for its `::cases()` and `->value` — the config provides the FQCN.
- The profiling logic is generic — keep as-is.
- Run `./run check:all` to verify no regressions.
