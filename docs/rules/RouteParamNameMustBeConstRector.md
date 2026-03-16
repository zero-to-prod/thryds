# RouteParamNameMustBeConstRector

Requires every `{param}` placeholder in a Route class pattern constant to have a matching `public const string` on the same class. Auto-generates the constant if it's missing.

## Before

```php
readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
}
```

## After

```php
readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
    public const string post = 'post';
    public const string comment = 'comment';
}
```

## Skips

```php
// All params already have constants — no change
readonly class PostRoute
{
    public const string pattern = '/posts/{post}';
    public const string post = 'post';
}

// No params in pattern — no change
readonly class HomeRoute
{
    public const string pattern = '/';
}
```

## Configuration

```php
$rectorConfig->ruleWithConfiguration(RouteParamNameMustBeConstRector::class, [
    'classSuffix' => 'Route',
    'constName' => 'pattern',
]);
```

| Key | Type | Description |
|-----|------|-------------|
| `classSuffix` | `string` | Only inspect classes whose name ends with this suffix. |
| `constName` | `string` | The constant name that holds the URL pattern (e.g., `'pattern'`). Also checks any constant whose value looks like a route pattern (contains `{...}` placeholders). |

## AST Strategy

- **Node type:** `Class_`
- **Match:** Class name ends with `classSuffix` and has a `ClassConst` named `constName` (or any constant whose string value contains `{...}`).
- **Param extraction:** Regex `\{(\w+)\}` on the constant's string value. Each match is a required param name.
- **Check:** For each extracted param, verify a `public const string $param = '$param'` exists on the class.
- **Action:** If missing, insert a new `ClassConst` node after the last existing constant. The constant is `public const string $param = '$param'`.

## Why

Path parameter names are the contract between route registration, URL generation, and request handling. When they're inline strings (`{post}`), renaming a param requires a manual find-and-replace across the pattern, the Route factory method, and any URL builder. With constants, IDE "Rename Symbol" on `PostRoute::post` updates everything atomically. This rule ensures the constants always exist.
