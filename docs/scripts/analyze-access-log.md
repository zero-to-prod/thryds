# Fix: analyze-access-log.php — Externalize Hardcoded Route Class and Log Path

## Script

`scripts/analyze-access-log.php`

## Violations

### 1. Hardcoded Route class import (line 19)

```php
use ZeroToProd\Thryds\Routes\Route;
```

### 2. Hardcoded default log path (line 21)

```php
$logPath = $argv[1] ?? dirname(__DIR__) . '/logs/frankenphp/access.log';
```

## Fix

1. Load `audit-config.yaml` (shared with `profile-endpoints.php`):

```php
$config = Yaml::parseFile(__DIR__ . '/../audit-config.yaml');
$routeClass = $config['route_class'];
$logPath = $argv[1] ?? dirname(__DIR__) . '/' . $config['access_log'];

$knownRoutes = array_fill_keys(array_column($routeClass::cases(), 'value'), true);
```

## Constraints

- The first CLI argument override for log path must continue to work.
- The analysis logic is generic — keep as-is.
- Run `./run check:all` to verify no regressions.
