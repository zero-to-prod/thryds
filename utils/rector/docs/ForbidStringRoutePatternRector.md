# ForbidStringRoutePatternRector

Flags inline string literals used as route patterns in `map()` calls and prompts extraction to a class constant.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Inline route pattern strings are magic values that are invisible to static analysis, cannot be refactored safely, and are easily duplicated. The project convention is that every route pattern lives in exactly one place — either as a `Route` enum case value or as a `public const string pattern` on a Route class. This rule is the first-line check that catches any raw string passed as the route pattern argument to `map()`.

## What It Detects

A `->map()` call (or any method listed in `methods`) where the argument at `argPosition` is a `String_` node (a PHP string literal). Class constant references and enum value property fetches are not flagged.

## Transformation

### In `warn` mode

Prepends a TODO comment to the `map()` statement. The comment includes the exact string value that was found.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `methods` | `string[]` | `['map']` | Method names to inspect |
| `argPosition` | `int` | `1` | Zero-based index of the route pattern argument |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` is a no-op |
| `message` | `string` | `"TODO: Extract route pattern '%s' to a class constant"` | Comment text; `%s` is replaced with the string value |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidStringRoutePatternRector::class, [
    'methods' => ['map'],
    'argPosition' => 1,
    'mode' => 'warn',
    'message' => "TODO: [ForbidStringRoutePatternRector] Replace inline string '%s' with a Route enum case reference (e.g. Route::case->value).",
]);
```

## Example

### Before

```php
$Router->map('GET', '/posts/{post}', $handler);
$Router->map('POST', '/login', $handler);
```

### After

```php
// TODO: Extract route pattern '/posts/{post}' to a class constant
$Router->map('GET', '/posts/{post}', $handler);
// TODO: Extract route pattern '/login' to a class constant
$Router->map('POST', '/login', $handler);
```

### Skipped (class constant reference)

```php
$Router->map('GET', HomeRoute::pattern, $handler);
$Router->map('GET', PostRoute::SHOW, $handler);
```

## Resolution

When you see the TODO comment from this rule:

1. Add the pattern as a case to the `Route` enum (e.g. `case posts = '/posts/{post}';`) or as `public const string pattern` on a dedicated Route class.
2. Replace the inline string in `map()` with `Route::posts->value` (enum path) or `PostsRoute::pattern` (class path).
3. Run `./run check:all` to confirm `RequireRouteEnumInMapCallRector` and `RequireAllRouteCasesRegisteredRector` are satisfied.

## Related Rules

- [`RequireRouteEnumInMapCallRector`](RequireRouteEnumInMapCallRector.md) — stricter check that requires the pattern to come specifically from the `Route` enum
- [`ExtractRoutePatternToRouteClassRector`](ExtractRoutePatternToRouteClassRector.md) — can automatically extract the string to a new Route class file
- [`ForbidHardcodedRouteStringRector`](ForbidHardcodedRouteStringRector.md) — flags route-value strings used anywhere, not just in `map()` calls
