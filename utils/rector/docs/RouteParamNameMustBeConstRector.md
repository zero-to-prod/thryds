# RouteParamNameMustBeConstRector

Automatically adds a `public const string paramName = 'paramName';` for each `{param}` placeholder found in a Route class's `pattern` constant.

**Category:** Route Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (in `auto` mode)

## Rationale

This rule applies to the Route *class* pattern. When a Route class defines `public const string pattern = '/posts/{post}/comments/{comment}';`, the parameter names `post` and `comment` appear as magic strings inside the URL template. Extracting each to its own constant (`public const string post = 'post';`) means that:

- Controllers and tests can reference `PostRoute::post` instead of the string `'post'` when reading request parameters.
- Renaming a URL parameter requires only changing the `pattern` value and the const value — all usages of the const update automatically.
- Static analysis can verify that param name references are valid.

The rule only adds constants that are missing; existing constants are left untouched.

## What It Detects

`Class_` nodes whose name ends with the configured `classSuffix` (default: `'Route'`) that contain a constant named `constName` (default: `'pattern'`) with a string value. The pattern value is parsed for `{param}` placeholders using regex. Any placeholder name that does not already have a corresponding constant in the class is considered missing.

## Transformation

### In `auto` mode

Inserts new `public const string paramName = 'paramName';` statements after the last existing constant in the class body, one per missing parameter name.

### In `warn` mode

Prepends a TODO comment to the class declaration (using the `message` configuration value).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `classSuffix` | `string` | `'Route'` | Class name suffix that identifies Route classes |
| `constName` | `string` | `'pattern'` | Name of the constant holding the URL pattern |
| `mode` | `string` | `'auto'` | `'auto'` inserts constants; `'warn'` adds a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RouteParamNameMustBeConstRector::class, [
    'classSuffix' => 'Route',
    'constName' => 'pattern',
    'mode' => 'auto',
]);
```

## Example

### Before

```php
readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
}
```

### After

```php
readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
    public const string post = 'post';
    public const string comment = 'comment';
}
```

### Skipped (all params already have constants)

```php
readonly class PostRoute
{
    public const string pattern = '/posts/{post}';
    public const string post = 'post';
}
```

### Skipped (pattern has no placeholders)

```php
readonly class HomeRoute
{
    public const string pattern = '/';
}
```

## Resolution

This rule runs in `auto` mode by default, so it self-resolves. If you see unexpected output:

1. Confirm the class name ends with `Route` and the constant is named `pattern`.
2. Check that the `pattern` value uses `{param}` syntax (curly-brace, alphanumeric name).
3. If a param constant has the wrong value, correct it manually — Rector only inserts missing constants, it does not overwrite existing ones.

## Related Rules

- [`RequireRoutePatternConstRector`](RequireRoutePatternConstRector.md) — ensures the `pattern` constant itself exists before this rule runs
- [`ForbidDuplicateRoutePatternRector`](ForbidDuplicateRoutePatternRector.md) — prevents two Route classes from sharing the same pattern value
- [`ExtractRoutePatternToRouteClassRector`](ExtractRoutePatternToRouteClassRector.md) — creates the initial Route class file including param constants
