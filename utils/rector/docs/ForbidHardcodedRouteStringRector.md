# ForbidHardcodedRouteStringRector

Flags any statement that contains a string literal whose value matches a `Route` enum case backing value, regardless of where in the codebase the string appears.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

`ForbidStringRoutePatternRector` and `RequireRouteEnumInMapCallRector` guard the route registration site. This rule guards the rest of the codebase: controller redirects, test assertions, link helpers, and any other place where a developer might hardcode a URL string that happens to be a registered route. Using `Route::case->value` everywhere makes route URLs a single source of truth — change the enum case value and all references update automatically.

The rule uses PHP reflection to load the `Route` enum at analysis time and build a map of `value => caseName`. Only strings that match a known route value are flagged; unrelated strings are left alone.

## What It Detects

Any `Expression` statement that contains a `String_` node whose value exists as a backing value in the configured `Route` enum. The check walks the entire subtree of each statement.

## Transformation

### In `warn` mode

Prepends a TODO comment to the statement containing the matching string. The comment names the enum case to use and the original string that was found.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClass` | `string` | `''` | FQCN of the Route backed enum |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; other values are a no-op |
| `message` | `string` | See source | Comment text; first `%s` is the case name, second `%s` is the original string |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidHardcodedRouteStringRector::class, [
    'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
    'mode' => 'warn',
    'message' => "TODO: [ForbidHardcodedRouteStringRector] Use Route::%s->value instead of hardcoded '%s'.",
]);
```

## Example

### Before

```php
$url = '/about';
$home = '/';
```

### After

```php
// TODO: [ForbidHardcodedRouteStringRector] Use Route::about->value instead of hardcoded '/about'.
$url = '/about';
// TODO: [ForbidHardcodedRouteStringRector] Use Route::home->value instead of hardcoded '/'.
$home = '/';
```

### Skipped (string is not a route value)

```php
$label = 'About Us';
```

## Resolution

When you see the TODO comment from this rule:

1. Replace the hardcoded string with `Route::caseName->value` using the case name shown in the comment.
2. Add the appropriate `use` import for the `Route` enum if not already present.
3. Run `./run check:all` to confirm the violation is resolved.

## Related Rules

- [`ForbidStringRoutePatternRector`](ForbidStringRoutePatternRector.md) — flags string literals specifically in `map()` call arguments
- [`RequireRouteEnumInMapCallRector`](RequireRouteEnumInMapCallRector.md) — enforces `Route::case->value` at the registration site
- [`RequireViewEnumInMakeCallRector`](RequireViewEnumInMakeCallRector.md) — analogous rule for View enum values in `make()` calls
