# ExtractRoutePatternToRouteClassRector

Auto-extracts inline string route patterns from `$Router->map()` calls into Route class constants. Generates the Route class file if it doesn't exist, including `public const string` entries for any path parameters found in the pattern.

## Before

```php
use League\Route\Router;

$Router->map('GET', '/posts/{post}', $handler);
$Router->map('GET', '/about', $handler);
```

## After

```php
use League\Route\Router;
use ZeroToProd\Thryds\Routes\PostsRoute;
use ZeroToProd\Thryds\Routes\AboutRoute;

$Router->map('GET', PostsRoute::pattern, $handler);
$Router->map('GET', AboutRoute::pattern, $handler);
```

### Generated: `src/Routes/PostsRoute.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

readonly class PostsRoute
{
    public const string pattern = '/posts/{post}';
    public const string post = 'post';
}
```

### Generated: `src/Routes/AboutRoute.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

readonly class AboutRoute
{
    public const string pattern = '/about';
}
```

## Skips

```php
// Already a class constant reference — no change
$Router->map('GET', HomeRoute::pattern, $handler);
```

## Configuration

```php
$rectorConfig->ruleWithConfiguration(ExtractRoutePatternToRouteClassRector::class, [
    'methods' => ['map'],
    'argPosition' => 1,
    'namespace' => 'ZeroToProd\\Thryds\\Routes',
    'outputDir' => __DIR__ . '/src/Routes',
]);
```

| Key | Type | Description |
|-----|------|-------------|
| `methods` | `string[]` | Method names to check on the Router. |
| `argPosition` | `int` | Zero-indexed position of the route pattern argument. |
| `namespace` | `string` | PHP namespace for generated Route classes. |
| `outputDir` | `string` | Filesystem path where Route class files are written. |

## AST Strategy

- **Node type:** `MethodCall`
- **Match:** Method name is in `methods`, and the argument at `argPosition` is a `String_` node.
- **Route class name derivation:** Take the first non-parameterized path segment after `/`, PascalCase it, append `Route`. E.g., `/posts/{post}` becomes `PostsRoute`, `/api/users/{id}/posts` becomes `UsersRoute`.
- **Path parameter extraction:** Regex `\{(\w+)\}` on the pattern string. Each match becomes a `public const string` on the generated class.
- **File generation:** Write the class file to `outputDir` only if it doesn't already exist. Use `readonly class` with `declare(strict_types=1)`.
- **AST replacement:** Replace the `String_` node with a `ClassConstFetch` node referencing the generated class's `pattern` constant. Add a `use` import for the class.

## Why

This is the auto-fix companion to `ForbidStringRoutePatternRector`. Instead of leaving a TODO, it performs the extraction. Run this rule first to migrate existing code, then enable `ForbidStringRoutePatternRector` to prevent regressions.
