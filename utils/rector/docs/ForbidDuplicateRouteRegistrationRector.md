# ForbidDuplicateRouteRegistrationRector

Flags duplicate route registrations where the same HTTP method and `Route` enum case are passed to `map()` more than once in the same file.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Registering the same HTTP method + route pattern twice causes the router to silently overwrite the first handler with the second. This is a logic bug that is difficult to spot visually in a long route file. Because route patterns are expressed as `Route::case->value`, the rule can identify duplicates symbolically (by enum case name) rather than by comparing raw strings.

The rule resets its seen-keys state per file, so cross-file duplicates are not flagged (they are prevented at a higher level by `ForbidDuplicateRoutePatternRector` for Route classes).

## What It Detects

A `->map()` call where:
- The HTTP method argument (at `methodArgPosition`) is either a plain string (`'GET'`) or an `HTTP_METHOD::GET->value` enum property fetch.
- The route argument (at `routeArgPosition`) is a `Route::caseName->value` property fetch.
- The combination of `HTTP_METHOD:caseName` has already been seen earlier in the same file.

The first occurrence is recorded; the second (and any subsequent) occurrence is flagged.

## Transformation

### In `warn` mode

Prepends a TODO comment to the duplicate `map()` statement. The comment identifies the HTTP method and enum case name that were already registered.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `methods` | `string[]` | `['map']` | Method names to inspect |
| `methodArgPosition` | `int` | `0` | Zero-based index of the HTTP method argument |
| `routeArgPosition` | `int` | `1` | Zero-based index of the route pattern argument |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective |
| `message` | `string` | See source | Comment text; first `%s` is the HTTP method, second `%s` is the case name |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidDuplicateRouteRegistrationRector::class, [
    'methods' => ['map'],
    'methodArgPosition' => 0,
    'routeArgPosition' => 1,
    'mode' => 'warn',
    'message' => "TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: '%s %s' was already registered above.",
]);
```

## Example

### Before

```php
$Router->map('GET', Route::home->value, $handlerA);
$Router->map('GET', Route::home->value, $handlerB);
```

### After

```php
$Router->map('GET', Route::home->value, $handlerA);
// TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: 'GET home' was already registered above.
$Router->map('GET', Route::home->value, $handlerB);
```

### Skipped (same route, different HTTP method)

```php
$Router->map('GET', Route::home->value, $handlerA);
$Router->map('POST', Route::home->value, $handlerB);
```

## Resolution

When you see the TODO comment from this rule:

1. Identify which of the two registrations is correct — usually the first one.
2. Remove the duplicate `map()` call, or consolidate the handlers if both are intentional (which is a design issue).
3. Run `./run check:all` to confirm no remaining violations.

## Related Rules

- [`RequireRouteEnumInMapCallRector`](RequireRouteEnumInMapCallRector.md) — ensures route patterns come from the `Route` enum (prerequisite for this rule to detect duplicates symbolically)
- [`ForbidDuplicateRoutePatternRector`](ForbidDuplicateRoutePatternRector.md) — catches duplicate URL patterns across Route *classes*
- [`RequireAllRouteCasesRegisteredRector`](RequireAllRouteCasesRegisteredRector.md) — verifies completeness of route registration
