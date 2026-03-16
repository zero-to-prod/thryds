# ForbidDuplicateRoutePatternRector

Forbids the same URL pattern string from appearing as a constant value in more than one Route class. Detects copy-paste errors and split-brain routing where two classes claim the same path.

## Before

```php
// src/Routes/HomeRoute.php
readonly class HomeRoute
{
    public const string pattern = '/';
}

// src/Routes/LandingRoute.php
readonly class LandingRoute
{
    public const string pattern = '/';
}
```

## After

```php
// src/Routes/HomeRoute.php
readonly class HomeRoute
{
    public const string pattern = '/';
}

// src/Routes/LandingRoute.php
readonly class LandingRoute
{
    // TODO: [ForbidDuplicateRoutePatternRector] Duplicate route pattern '/'. This pattern is already defined in HomeRoute::pattern. Remove or rename this constant.
    public const string pattern = '/';
}
```

## Skips

```php
// Unique patterns — no change
readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class AboutRoute
{
    public const string pattern = '/about';
}
```

## Configuration

```php
$rectorConfig->ruleWithConfiguration(ForbidDuplicateRoutePatternRector::class, [
    'classSuffix' => 'Route',
    'constNames' => ['pattern'],
    'scanDir' => __DIR__ . '/src/Routes',
]);
```

| Key | Type | Description |
|-----|------|-------------|
| `classSuffix` | `string` | Only inspect classes whose name ends with this suffix. |
| `constNames` | `string[]` | Constant names to check for duplicates (e.g., `['pattern', 'SHOW', 'STORE']`). |
| `scanDir` | `string` | Directory to scan for Route class files. Used to build the full duplicate map before processing. |

## AST Strategy

- **Node type:** `FileNode` (first pass to build map), `ClassConst` (second pass to flag).
- **First pass:** Scan all files in `scanDir` matching `classSuffix`. Build a map of `pattern value => [class, file]` for each constant in `constNames`.
- **Second pass:** For each `ClassConst` node where the name is in `constNames` and the value is a duplicate, add a TODO comment identifying the other class that defines the same pattern.
- **Action:** Add a TODO comment above the duplicate constant. Do not add if a `[ForbidDuplicateRoutePatternRector]` comment already exists.

## Why

When every route pattern lives on its own class, accidental duplication means two classes claim the same URL. The router will silently use one and ignore the other. This rule catches that at refactor time, not at runtime.
