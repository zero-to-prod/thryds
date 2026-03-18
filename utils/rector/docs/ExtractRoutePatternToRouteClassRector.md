# ExtractRoutePatternToRouteClassRector

Extracts inline string route patterns from `map()` calls into dedicated Route class files, replacing the string argument with a reference to the new class's `pattern` constant.

**Category:** Route Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (in `auto` mode)

## Rationale

This rule applies to the Route *class* pattern, not the `Route` enum pattern. It is the migration tool: given a codebase that still uses inline strings in `map()` calls, it automatically generates the Route class file (with `public const string pattern` and one `public const string` per URL parameter) and rewrites the call site to reference `RouteClassName::pattern`. This is a one-time transformation that bootstraps the Route class convention from a raw string.

The generated class is a `readonly class` with a `public const string pattern` and one param constant per `{placeholder}` in the URL. The class file is written to `outputDir` and is skipped if a file with the same name already exists, making the rule idempotent.

In `warn` mode the rule does not create files or rewrite code; it only adds a TODO comment to alert the developer.

## What It Detects

`MethodCall` nodes that match one of the names in `methods` where the argument at `argPosition` is a `String_` node (a PHP string literal). Non-string arguments (e.g. class constant references) are skipped.

## Class Name Derivation

The generated class name is derived from the first non-parameter URL segment:
- `/` → `HomeRoute`
- `/posts/{post}` → `PostsRoute` (first non-param segment `posts`, ucfirst)
- `/about` → `AboutRoute`

## Transformation

### In `auto` mode

1. Derives a class name from the pattern string.
2. Writes a new `readonly class ClassName { public const string pattern = '...'; public const string param = 'param'; ... }` file to `outputDir` (skipped if the file already exists).
3. Rewrites the `map()` argument from the string literal to `\Full\Namespace\ClassName::pattern`.

### In `warn` mode

Prepends a TODO comment to the `map()` call without creating any files or rewriting any code.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `methods` | `string[]` | `[]` | Method names to inspect |
| `argPosition` | `int` | `1` | Zero-based index of the route pattern argument |
| `namespace` | `string` | `''` | PHP namespace for generated Route classes |
| `outputDir` | `string` | `''` | Filesystem directory where generated class files are written |
| `mode` | `string` | `'auto'` | `'auto'` to generate files and rewrite; `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ExtractRoutePatternToRouteClassRector::class, [
    'methods' => ['map'],
    'argPosition' => 1,
    'namespace' => 'ZeroToProd\\Thryds\\Routes',
    'outputDir' => __DIR__ . '/src/Routes',
    'mode' => 'warn',
    'message' => 'TODO: [ExtractRoutePatternToRouteClassRector] Extract inline route string to a Route class constant.',
]);
```

## Example

### Before

```php
$Router->map('GET', '/posts/{post}', $handler);
```

### After (call site)

```php
$Router->map('GET', \App\Routes\PostsRoute::pattern, $handler);
```

### Generated file (`src/Routes/PostsRoute.php`)

```php
<?php

declare(strict_types=1);

namespace App\Routes;

readonly class PostsRoute
{
    public const string pattern = '/posts/{post}';
    public const string post = 'post';
}
```

### Skipped (already a class constant reference)

```php
$Router->map('GET', HomeRoute::pattern, $handler);
```

## Resolution

When you see the TODO comment from this rule (in `warn` mode):

1. Create the Route class manually in `src/Routes/` with `public const string pattern = '/your/url';`.
2. Add one `public const string paramName = 'paramName';` per `{param}` in the pattern.
3. Replace the inline string in `map()` with `ClassName::pattern`.
4. Run `./run check:all` — `RequireRoutePatternConstRector` and `RouteParamNameMustBeConstRector` will validate the new class.

Alternatively, switch the rule to `'mode' => 'auto'` temporarily, run Rector, then switch back to `'warn'`.

## Related Rules

- [`RequireRoutePatternConstRector`](RequireRoutePatternConstRector.md) — verifies the generated class has a `pattern` constant
- [`RouteParamNameMustBeConstRector`](RouteParamNameMustBeConstRector.md) — adds missing param constants to the generated class
- [`ForbidStringRoutePatternRector`](ForbidStringRoutePatternRector.md) — flags inline strings in `map()` calls (the problem this rule solves)
- [`ForbidDuplicateRoutePatternRector`](ForbidDuplicateRoutePatternRector.md) — prevents two Route classes from sharing the same pattern value
