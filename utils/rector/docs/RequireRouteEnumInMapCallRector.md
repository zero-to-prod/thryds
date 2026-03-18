# RequireRouteEnumInMapCallRector

Requires that the route pattern argument to `map()` is always a `Route` enum case value (`Route::case->value`), not a string literal or any other class constant reference.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

`ForbidStringRoutePatternRector` catches raw strings, but a developer could still use another class's constant (e.g. `SomeClass::PATTERN`) and satisfy that rule while bypassing the project convention. This rule is the stricter complement: it verifies that the pattern comes specifically from the designated `Route` backed enum via `->value`. This ensures every registered route is traceable to a canonical enum case, enabling `RequireAllRouteCasesRegisteredRector` to verify completeness.

The rule also handles one explicit exemption: `map()` calls that appear inside a `foreach (Route::cases() as ...)` loop are skipped, because that pattern is the approved dynamic registration style.

## What It Detects

Any `->map()` call where the argument at `argPosition` is not a `PropertyFetch` of the form `Route::caseName->value` (where `Route` resolves to the configured `enumClass`). This includes:

- String literals (`'/posts/{post}'`)
- Other class constants (`SomeClass::PATTERN`)
- Variables or expressions

## Transformation

### In `warn` mode

Prepends a TODO comment to the `map()` statement. The comment shows what was found instead of a proper enum value reference.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClass` | `string` | `'ZeroToProd\\Thryds\\Routes\\Route'` | The FQCN of the Route enum whose `->value` is the only accepted pattern source |
| `methods` | `string[]` | `['map']` | Method names to inspect |
| `argPosition` | `int` | `1` | Zero-based index of the route pattern argument |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` is a no-op |
| `message` | `string` | See source | Comment text; `%s` is replaced with the display value of what was found |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireRouteEnumInMapCallRector::class, [
    'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
    'methods' => ['map'],
    'argPosition' => 1,
    'mode' => 'warn',
    'message' => "TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '%s' instead.",
]);
```

## Example

### Before (string literal)

```php
$Router->map('GET', '/posts/{post}', $handler);
$Router->map('POST', '/login', $handler);
```

### After (string literal)

```php
// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '/posts/{post}' instead.
$Router->map('GET', '/posts/{post}', $handler);
// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '/login' instead.
$Router->map('POST', '/login', $handler);
```

### Before (wrong class constant)

```php
$Router->map('GET', SomeClass::PATTERN, $handler);
```

### After (wrong class constant)

```php
// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found 'SomeClass::PATTERN' instead.
$Router->map('GET', SomeClass::PATTERN, $handler);
```

### Skipped (correct enum value)

```php
$Router->map('GET', ZeroToProd\Thryds\Routes\Route::home->value, $handler);
```

### Skipped (dynamic registration via `cases()` loop)

```php
foreach (ZeroToProd\Thryds\Routes\Route::cases() as $Route) {
    $Router->map('GET', $Route->value, $handler);
}
```

## Resolution

When you see the TODO comment from this rule:

1. Locate the `Route` enum and find the case that corresponds to the pattern being registered, or add a new case.
2. Replace the argument with `Route::caseName->value`.
3. Run `./run check:all` — `RequireAllRouteCasesRegisteredRector` will confirm the case is now registered.

## Related Rules

- [`ForbidStringRoutePatternRector`](ForbidStringRoutePatternRector.md) — the first-pass check that flags any string literal in `map()`
- [`RequireAllRouteCasesRegisteredRector`](RequireAllRouteCasesRegisteredRector.md) — verifies every `Route` enum case has a corresponding `map()` call
- [`ForbidDuplicateRouteRegistrationRector`](ForbidDuplicateRouteRegistrationRector.md) — prevents the same case from being registered twice
