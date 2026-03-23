# RequireRouteTestRector

Flags `Route` enum cases that have no test referencing them in the configured test directory.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No (stale TODOs are removed automatically)

## Rationale

A route that is registered but never exercised by any test is a liability: regressions go undetected and the route's contract is undefined. This rule enforces a one-to-one coverage requirement between the `Route` enum (the source of truth for all routes) and the test suite. A case is considered tested if any `.php` file under `testDir` contains a reference to the enum case name (e.g. `Route::home` or `TestRoute::about`).

Like `RequireAllRouteCasesRegisteredRector`, this rule is self-healing: stale TODO comments are removed automatically when the corresponding test is added.

## What It Detects

`Enum_` nodes whose fully-qualified name matches `enumClass`. For each `EnumCase`, the rule scans all `.php` files under `testDir` looking for patterns of the form `Route::caseName` (using both the short class name and the fully-qualified name). Case names that start with an uppercase letter (e.g. `Route::cases()`) are excluded to avoid false matches on static method calls.

## Transformation

### In `warn` mode

Prepends a TODO comment to each untested `EnumCase` statement inside the enum body. When a test is added, the comment is removed on the next Rector run.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClass` | `string` | `''` | FQCN of the Route enum to inspect |
| `testDir` | `string` | `''` | Directory to recursively scan for test files |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` is a no-op |
| `message` | `string` | See source | Comment text; `%s` is replaced with the enum case name |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireRouteTestRector::class, [
    'enumClass' => \ZeroToProd\Thryds\Routes\RouteList::class,
    'testDir' => __DIR__ . '/tests',
    'mode' => 'warn',
    'message' => "TODO: [RequireRouteTestRector] Route case '%s' has no corresponding test. Add a test that exercises this route.",
]);
```

## Example

### Before

```php
enum Route: string
{
    case home = '/';
    case about = '/about';
    case untested = '/untested';
}
```

### After

```php
enum Route: string
{
    case home = '/';
    case about = '/about';
    // TODO: [RequireRouteTestRector] Route case 'untested' has no corresponding test. Add a test that exercises this route.
    case untested = '/untested';
}
```

### Stale TODO removal

Once a test file contains `Route::untested` (or `Route::untested->value`, etc.), the next Rector run removes the comment:

```php
// Before (stale):
// TODO: [RequireRouteTestRector] Route case 'home' has no corresponding test. Add a test that exercises this route.
case home = '/';

// After Rector run (test now exists):
case home = '/';
```

## Resolution

When you see the TODO comment from this rule:

1. Create or update a test file under `tests/` that exercises the route (e.g. makes an HTTP request to the route and asserts the expected response).
2. Ensure the test file contains a reference to `Route::caseName` so the scanner can detect it.
3. Run `./run check:all` — Rector will remove the TODO comment automatically.

## Related Rules

- [`RequireAllRouteCasesRegisteredRector`](RequireAllRouteCasesRegisteredRector.md) — verifies that each Route case is registered in the router
- [`RequireRouteEnumInMapCallRector`](RequireRouteEnumInMapCallRector.md) — ensures registrations are traceable to enum cases
