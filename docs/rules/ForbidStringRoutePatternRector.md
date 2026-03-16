# ForbidStringRoutePatternRector

Forbids inline string literals as route patterns in `$Router->map()` calls. Route patterns must be class constant references (e.g., `HomeRoute::pattern`), enforcing a single source of truth for URL patterns.

## Before

```php
$Router->map('GET', '/posts/{post}', $handler);
$Router->map('POST', '/login', $handler);
```

## After

```php
// TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '/posts/{post}' to a Route class constant.
$Router->map('GET', '/posts/{post}', $handler);
// TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '/login' to a Route class constant.
$Router->map('POST', '/login', $handler);
```

## Skips

```php
// Already using a class constant — no change
$Router->map('GET', HomeRoute::pattern, $handler);
$Router->map('GET', PostRoute::SHOW, $handler);
```

## Configuration

```php
$rectorConfig->ruleWithConfiguration(ForbidStringRoutePatternRector::class, [
    'methods' => ['map'],
    'argPosition' => 1,
]);
```

| Key | Type | Description |
|-----|------|-------------|
| `methods` | `string[]` | Method names to check on the Router (e.g., `['map', 'get', 'post']`). |
| `argPosition` | `int` | Zero-indexed position of the route pattern argument. |

## AST Strategy

- **Node type:** `MethodCall`
- **Match:** Method name is in `methods`, and the argument at `argPosition` is a `String_` node.
- **Action:** Add a TODO comment to the enclosing `Expression` node. Do not add if a `[ForbidStringRoutePatternRector]` comment already exists.

## Why

An AI agent grepping for a URL pattern should land on a Route class, not a registration file. Inline strings are invisible to static analysis and IDE refactoring. This rule catches violations at the point of use — the `$Router->map()` call — before they ship.
