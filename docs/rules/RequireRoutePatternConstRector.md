# RequireRoutePatternConstRector

Requires every Route class to define at least one route pattern constant. Catches empty or stub Route classes that were created but never given a URL pattern.

## Before

```php
readonly class PostRoute
{
    public const string post = 'post';
}
```

## After

```php
// TODO: [RequireRoutePatternConstRector] Route class 'PostRoute' is missing a 'pattern' constant. Define: public const string pattern = '/...';
readonly class PostRoute
{
    public const string post = 'post';
}
```

## Skips

```php
// Has a pattern constant — no change
readonly class PostRoute
{
    public const string pattern = '/posts/{post}';
    public const string post = 'post';
}

// Not a Route class (no matching suffix) — no change
readonly class Config
{
    public const string appEnv = 'appEnv';
}
```

## Configuration

```php
$rectorConfig->ruleWithConfiguration(RequireRoutePatternConstRector::class, [
    'classSuffix' => 'Route',
    'constName' => 'pattern',
    'excludedClasses' => [],
]);
```

| Key | Type | Description |
|-----|------|-------------|
| `classSuffix` | `string` | Only inspect classes whose name ends with this suffix. |
| `constName` | `string` | The constant name that must exist (e.g., `'pattern'`). |
| `excludedClasses` | `string[]` | Fully qualified class names to skip (e.g., abstract base routes or registry classes like `WebRoutes`). |

## AST Strategy

- **Node type:** `Class_`
- **Match:** Class name ends with `classSuffix`, is not in `excludedClasses`, and does not have a `ClassConst` node named `constName`.
- **Action:** Add a TODO comment above the class declaration. Do not add if a `[RequireRoutePatternConstRector]` comment already exists.

## Why

A Route class without a pattern constant is dead weight — it exists in the codebase but doesn't define any URL. This usually means someone created the class skeleton but forgot to fill it in, or a refactor moved the pattern elsewhere without cleaning up the class.
