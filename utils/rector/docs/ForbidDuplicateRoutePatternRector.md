# ForbidDuplicateRoutePatternRector

Flags Route *classes* that define a `pattern` constant whose value is already defined by another Route class in the scanned source directory.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

This rule applies to the Route *class* pattern. If two Route classes both define `public const string pattern = '/';`, they represent an ambiguous routing conflict: two classes claim ownership of the same URL. Because Route class files are the canonical source of route patterns in this variant of the routing convention, duplicate pattern values are a design error that must be resolved at the class level. The rule cross-references all Route classes found under `scanDir` to detect conflicts.

## What It Detects

`Class_` nodes whose short name ends with `classSuffix` (default: `'Route'`) and that contain a constant whose name is in `constNames` (default: `['pattern']`) with a string literal value that has already been recorded from another Route class (either from the pre-scan of `scanDir` or from a class processed earlier in the same Rector run).

## Transformation

### In `warn` mode

Prepends a TODO comment to the `ClassConst` statement (the constant declaration) that contains the duplicate value. The comment names the pattern, the first class that defines it, and the constant name.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `classSuffix` | `string` | `'Route'` | Class name suffix that identifies Route classes |
| `constNames` | `string[]` | `['pattern']` | Constant names to check for duplicate values |
| `scanDir` | `string` | `''` | Directory to pre-scan for existing pattern definitions |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` is a no-op |
| `message` | `string` | See source | Comment text; `%s` placeholders receive pattern value, first class name, and const name |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidDuplicateRoutePatternRector::class, [
    'classSuffix' => 'Route',
    'constNames' => ['pattern'],
    'scanDir' => __DIR__ . '/src',
    'mode' => 'warn',
    'message' => "TODO: [ForbidDuplicateRoutePatternRector] Duplicate route pattern '%s' — already defined in %s::%s.",
]);
```

## Example

### Before

```php
readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class LandingRoute
{
    public const string pattern = '/';
}
```

### After

```php
readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class LandingRoute
{
    // TODO: Duplicate route pattern '/' — already defined in HomeRoute::pattern
    public const string pattern = '/';
}
```

### Skipped (unique patterns)

```php
readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class AboutRoute
{
    public const string pattern = '/about';
}
```

## Resolution

When you see the TODO comment from this rule:

1. Determine which class is the canonical owner of the pattern. Usually this is the first class (named in the comment).
2. Delete the duplicate class, or change its pattern to the correct, unique URL.
3. Update any `map()` calls or references that pointed to the duplicate class.
4. Run `./run check:all` to confirm no remaining violations.

## Related Rules

- [`RequireRoutePatternConstRector`](RequireRoutePatternConstRector.md) — ensures each Route class has a `pattern` constant (prerequisite)
- [`ForbidDuplicateRouteRegistrationRector`](ForbidDuplicateRouteRegistrationRector.md) — catches duplicate registrations in the router (for the `Route` enum pattern)
- [`RouteParamNameMustBeConstRector`](RouteParamNameMustBeConstRector.md) — extracts param names from a Route class's pattern constant
