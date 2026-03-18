# RequireRoutePatternConstRector

Flags Route *classes* (classes whose name ends with the configured suffix, e.g. `Route`) that are missing a `public const string pattern` constant.

**Category:** Route Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

This rule applies to the Route *class* pattern, which is distinct from the `Route` enum pattern. In the Route class pattern, each route is represented by a dedicated readonly class (e.g. `PostsRoute`, `HomeRoute`) that holds its URL pattern as `public const string pattern = '/posts/{post}';`. Without this constant, the class cannot be referenced in `map()` calls, and `RouteParamNameMustBeConstRector` cannot extract the parameter names. This rule acts as the foundational guard: a class that matches the naming convention must define the `pattern` constant.

## What It Detects

`Class_` nodes (non-anonymous) whose short name ends with the configured `classSuffix` (default: `'Route'`) and that do not contain a constant named `constName` (default: `'pattern'`). Classes in the `excludedClasses` list are skipped.

## Transformation

### In `warn` mode

Prepends a TODO comment to the class declaration. The comment includes the class name and the expected constant name and shows the definition syntax.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `classSuffix` | `string` | `'Route'` | Class name suffix that identifies Route classes |
| `constName` | `string` | `'pattern'` | Name of the expected constant |
| `excludedClasses` | `string[]` | `[]` | Short class names to skip |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` is a no-op |
| `message` | `string` | See source | Comment text; `%s` placeholders receive class name and const name |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireRoutePatternConstRector::class, [
    'classSuffix' => 'Route',
    'constName' => 'pattern',
    'mode' => 'warn',
    'message' => "TODO: [RequireRoutePatternConstRector] Route class '%s' is missing a '%s' constant — define: public const string %s = '/...';",
]);
```

## Example

### Before

```php
readonly class PostRoute
{
    public const string post = 'post';
}
```

### After

```php
// TODO: Route class 'PostRoute' is missing a 'pattern' constant — define: public const string pattern = '/...';
readonly class PostRoute
{
    public const string post = 'post';
}
```

### Skipped (pattern constant present)

```php
readonly class PostRoute
{
    public const string pattern = '/posts/{post}';
    public const string post = 'post';
}
```

## Resolution

When you see the TODO comment from this rule:

1. Add `public const string pattern = '/your/url/here';` to the Route class, replacing the placeholder with the actual URL pattern.
2. If the pattern contains `{param}` placeholders, `RouteParamNameMustBeConstRector` will automatically add the corresponding param constants on the next Rector run.
3. Run `./run check:all` to confirm no remaining violations.

## Related Rules

- [`RouteParamNameMustBeConstRector`](RouteParamNameMustBeConstRector.md) — extracts `{param}` names from the `pattern` constant into individual constants
- [`ForbidDuplicateRoutePatternRector`](ForbidDuplicateRoutePatternRector.md) — prevents two Route classes from sharing the same pattern value
- [`ExtractRoutePatternToRouteClassRector`](ExtractRoutePatternToRouteClassRector.md) — can automatically create a Route class file from an inline string pattern
