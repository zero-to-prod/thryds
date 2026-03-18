# ForbidDirectRouterInstantiationRector

Flags direct instantiation of `League\Route\Router` and requires the use of `League\Route\Cache\Router` instead.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Instantiating `League\Route\Router` directly bypasses route caching. The project uses `League\Route\Cache\Router`, which wraps the router in a cache layer and avoids the cost of re-parsing and re-compiling route patterns on every request. Direct instantiation is a performance risk, especially in worker mode where routes are registered once and reused across many requests.

## What It Detects

Any `new` expression that instantiates a class listed in `forbiddenClasses`. In the project configuration this is `League\Route\Router`.

## Transformation

### In `warn` mode

Prepends a TODO comment to the statement containing the forbidden instantiation. The comment names the class that was matched.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `forbiddenClasses` | `string[]` | `[]` | Fully-qualified class names whose direct instantiation is forbidden |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` is a no-op for this rule |
| `message` | `string` | `'TODO: Avoid direct instantiation of %s — use a cached router instead'` | Comment text; `%s` is replaced with the matched class name |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidDirectRouterInstantiationRector::class, [
    'forbiddenClasses' => [Router::class],
    'mode' => 'warn',
    'message' => 'TODO: [ForbidDirectRouterInstantiationRector] Use League\\Route\\Cache\\Router instead of instantiating %s directly. Direct instantiation bypasses route caching.',
]);
```

## Example

### Before

```php
$router = new League\Route\Router();
```

### After

```php
// TODO: Avoid direct instantiation of League\Route\Router — use a cached router instead
$router = new League\Route\Router();
```

## Resolution

When you see the TODO comment from this rule:

1. Replace `new League\Route\Router()` with `new League\Route\Cache\Router($callback)`, where `$callback` is a `callable` that accepts a `RouterInterface` and registers all routes on it.
2. Verify the cached router is configured with an appropriate cache store (e.g., a filesystem adapter) so the cache is persisted across requests.
3. Remove the TODO comment once the fix is in place and `./run check:all` passes.

## Related Rules

- [`ForbidStringRoutePatternRector`](ForbidStringRoutePatternRector.md) — flags inline string route patterns that should be extracted to constants
- [`RequireRouteEnumInMapCallRector`](RequireRouteEnumInMapCallRector.md) — requires `Route::case->value` as the pattern argument to `map()`
- [`ForbidDuplicateRouteRegistrationRector`](ForbidDuplicateRouteRegistrationRector.md) — flags the same route being registered more than once
