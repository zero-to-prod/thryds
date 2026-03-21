# Fix: opcache-audit.php — Externalize Hardcoded Paths and Class References

## Script

`scripts/opcache-audit.php`

## Violations

### 1. Hardcoded PHP file count paths (lines 118-119)

```php
$appFiles = countPhpFiles('/app/src');
$vendorFiles = countPhpFiles('/app/vendor');
```

### 2. Hardcoded class references (lines 18-19)

```php
use ZeroToProd\Thryds\OpcacheStatus;
use ZeroToProd\Thryds\Routes\Route;
```

The script depends on project-specific `OpcacheStatus` constants and `Route` enum for warming and status endpoints.

### 3. Hardcoded DevPath reference (line 224)

```php
\ZeroToProd\Thryds\DevPath::cases()
```

## Fix

1. Create `opcache-config.yaml` at the project root:

```yaml
source_dirs:
  - /app/src
  - /app/vendor
status_class: ZeroToProd\Thryds\OpcacheStatus
route_class: ZeroToProd\Thryds\Routes\Route
dev_path_enum: ZeroToProd\Thryds\DevPath
preload_marker: $PRELOAD$
preload_file: /app/preload.php
```

2. In the script, load the config and replace hardcoded values:

```php
$config = Yaml::parseFile(__DIR__ . '/../opcache-config.yaml');
$totalFiles = 0;
foreach ($config['source_dirs'] as $dir) {
    $totalFiles += countPhpFiles($dir);
}
```

3. For the Route and OpcacheStatus classes, load FQCNs from config and use them dynamically.

## Constraints

- The `opcacheAudit()` function uses `OpcacheStatus::*` constants extensively — these are array keys from the OPcache status response. Consider whether these belong in config or remain as constants on the class.
- The `warmRoutes()` function needs access to Route enum cases — the config can provide the route class FQCN.
- Run `./run check:all` to verify no regressions.
