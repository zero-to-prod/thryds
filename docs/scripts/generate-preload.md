# Fix: generate-preload.php — Externalize Hardcoded Class References, Path Groups, and Prefix

## Script

`scripts/generate-preload.php`

## Violations

### 1. Hardcoded class instantiations for dependency loading (lines 67-83)

```php
new \Laminas\Diactoros\Response\JsonResponse(data: []);
\Laminas\Diactoros\ServerRequestFilter\IPRange::matches('127.0.0.1', '127.0.0.0/8');
new \League\Route\Cache\Router(...);
class_exists(\ZeroToProd\Thryds\App::class);
new \ZeroToProd\Thryds\RequestId();
new \Illuminate\Support\Collection();
// ...etc
```

These force-loaded classes are project-specific and will change as dependencies evolve.

### 2. Hardcoded preload group definitions (lines 361-389)

```php
$groups = [
    'Autoload' => [],
    'Helpers' => [],
    'Core' => [],
    'Routes' => [],
    'ViewModels' => [],
    'Entrypoint' => [],
    'Vendor' => [],
];
```

Group-to-path mappings (e.g., `src/Helpers/` → Helpers, `src/Routes/` → Routes) are hardcoded.

### 3. Hardcoded `/app/` prefix (lines 106, 111, 372, 399)

```php
if (!str_starts_with($path, '/app/')) { continue; }
$path = str_replace(dirname(__DIR__), '/app', $path);
$rel = str_replace('/app/', '', $script);
```

### 4. Hardcoded DevPath class reference (line 115)

```php
\ZeroToProd\Thryds\DevPath::cases()
```

## Fix

1. Create `preload-config.yaml` at the project root:

```yaml
container_prefix: /app/
dev_path_enum: ZeroToProd\Thryds\DevPath
force_load_classes:
  - Laminas\Diactoros\Response\JsonResponse
  - Laminas\Diactoros\ServerRequestFilter\IPRange
  - League\Route\Cache\Router
  - ZeroToProd\Thryds\App
  - ZeroToProd\Thryds\RequestId
  - Illuminate\Support\Collection
  - Illuminate\Support\Stringable
  - Illuminate\View\Compilers\ComponentTagCompiler
  - Laravel\SerializableClosure\Support\ClosureStream
  - Laravel\SerializableClosure\Support\ClosureScope
  - Psr\SimpleCache\CacheInterface
groups:
  Autoload:
    match: vendor/autoload.php
  Helpers:
    prefix: src/Helpers/
  Core:
    prefix: src/
  Routes:
    prefix: src/Routes/
  ViewModels:
    prefix: src/ViewModels/
  Entrypoint:
    prefix: public/
  Vendor:
    prefix: vendor/
```

2. Replace the force-load block with a loop over `force_load_classes`:

```php
foreach ($config['force_load_classes'] as $class) {
    class_exists($class);
}
```

3. Replace the hardcoded groups with config-driven classification.

4. Replace `/app/` with `$config['container_prefix']`.

## Constraints

- Some classes require `new` (not just `class_exists`) to trigger side-effect loading — document which need instantiation in the config.
- The topological sort logic (`resolveOrder`, `parseClassInfo`) is generic — keep as-is.
- The `League\Route\Cache\Router` force-load requires constructor args — handle as a special case or document.
- Run `./run check:all` to verify no regressions.
