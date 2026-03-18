# RequireAllRouteCasesRegisteredRector

Flags any `Route` enum case that is defined in the enum but never passed to a `map()` call in the scanned source directory.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No (stale TODOs are removed automatically)

## Rationale

The `Route` enum is the single source of truth for all URL patterns in the project. Adding a new case to the enum without registering it in the router means that case is dead: the URL does not exist. This rule performs a full-coverage check by scanning all PHP files under `scanDir` for `map()` calls that reference `Route::caseName->value`, then annotates any enum case that has no corresponding registration.

The rule is self-healing: if a previously unregistered case is later registered, the stale TODO comment is automatically removed on the next Rector run.

One special case: if the scan finds a `foreach (Route::cases() as ...)` loop, the rule assumes all cases are registered dynamically and suppresses all warnings (removing any existing TODO comments).

## What It Detects

`Enum_` nodes whose fully-qualified name matches `enumClass`. For each `EnumCase` in the enum, the rule checks whether its name appears as the case identifier in any `Route::caseName->value` expression found in any `.php` file under `scanDir`.

## Transformation

### In `warn` mode

Prepends a TODO comment to each unregistered `EnumCase` statement inside the enum body. If the case is later registered, the comment is removed.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClass` | `string` | `'ZeroToProd\\Thryds\\Routes\\Route'` | FQCN of the Route enum to inspect |
| `methods` | `string[]` | `['map']` | Method names to look for in source files |
| `argPosition` | `int` | `1` | Zero-based index of the route pattern argument in `map()` |
| `scanDir` | `string` | `''` | Directory to recursively scan for route registrations |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective |
| `message` | `string` | See source | Comment text; `%s` is replaced with the enum case name |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireAllRouteCasesRegisteredRector::class, [
    'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
    'methods' => ['map'],
    'argPosition' => 1,
    'scanDir' => __DIR__ . '/src',
    'mode' => 'warn',
    'message' => "TODO: [RequireAllRouteCasesRegisteredRector] Route case '%s' is defined but never registered in any router map() call.",
]);
```

## Example

### Before

```php
enum Route: string
{
    case home = '/';
    case about = '/about';
    case unregistered = '/unregistered';
}
```

### After

```php
enum Route: string
{
    case home = '/';
    case about = '/about';
    // TODO: [RequireAllRouteCasesRegisteredRector] Route case 'unregistered' is defined but never registered in any router map() call.
    case unregistered = '/unregistered';
}
```

### Stale TODO removal

If `home` was previously flagged but is now registered, the next Rector run removes the comment:

```php
// Before Rector run (stale):
// TODO: [RequireAllRouteCasesRegisteredRector] Route case 'home' is defined but never registered in any router map() call.
case home = '/';

// After Rector run:
case home = '/';
```

## Resolution

When you see the TODO comment from this rule:

1. Open the router registration file (typically in `src/`).
2. Add `$router->map(HTTP_METHOD::GET->value, Route::unregistered->value, $handler);` with the appropriate HTTP method and handler.
3. Run `./run check:all` — Rector will remove the TODO comment automatically once the case is registered.

## Related Rules

- [`RequireRouteEnumInMapCallRector`](RequireRouteEnumInMapCallRector.md) — ensures registrations use `Route::case->value` (prerequisite for this rule's scan to detect them)
- [`RequireRouteTestRector`](RequireRouteTestRector.md) — companion rule that verifies each route also has a test
- [`ForbidDuplicateRouteRegistrationRector`](ForbidDuplicateRouteRegistrationRector.md) — prevents a case from being registered more than once
