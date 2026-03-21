# Fix: production-checklist.php — Externalize Hardcoded Paths, Namespaces, and Sub-checks

## Script

`scripts/production-checklist.php`

## Violations

### 1. Hardcoded component template directory (line 113)

```php
$template_dir = $base_dir . '/templates/components';
```

### 2. Hardcoded namespace imports (lines 22-27)

```php
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Config;
```

### 3. Hardcoded sub-check scripts (lines 40, 49)

```php
runScript('php ' . escapeshellarg(__DIR__ . '/verify-route-cache.php'));
runScript('php ' . escapeshellarg(__DIR__ . '/opcache-audit.php'));
```

### 4. Hardcoded Vite entry config (lines 208-210)

```php
$Vite = new Vite($Config, baseDir: $base_dir, entry_css: [
    Vite::app_entry => [Vite::app_css],
]);
```

## Fix

1. Create `production-config.yaml` at the project root:

```yaml
checks:
  - name: Route Cache
    command: "php scripts/verify-route-cache.php"
  - name: OPcache
    command: "php scripts/opcache-audit.php"
  - name: Template Cache
    type: template-cache
  - name: Component @push Directives
    type: component-push
template_dir: templates
component_dir: templates/components
cache_dir: var/cache/blade
namespaces:
  app: ZeroToProd\Thryds\App
  view: ZeroToProd\Thryds\Blade\View
  component: ZeroToProd\Thryds\Blade\Component
  vite: ZeroToProd\Thryds\Blade\Vite
  config: ZeroToProd\Thryds\Config
```

2. Load the config and drive the checklist from it.

## Constraints

- The `verifyTemplateCache()` and `verifyComponentPushDirectives()` functions contain logic that references `View::cases()`, `Component::cases()`, and `App::bootBlade()` — these should be driven by config FQCNs.
- Run `./run check:all` to verify no regressions.
