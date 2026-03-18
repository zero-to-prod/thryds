# ForbidDynamicIncludeRector

Flags `include`/`require` statements whose path cannot be resolved at compile time.

**Category:** OPcache Optimization
**Mode:** `warn`
**Auto-fix:** No

## Rationale

OPcache precompiles PHP files to bytecode and caches them on disk. For a file to be cached, OPcache must know its path at compile time. When `require` or `include` receives a variable, a runtime expression, or anything other than a static string or `__DIR__`/`__FILE__` concatenation, OPcache cannot precompile the target — it must parse and compile the file on every request that reaches that branch. In a high-throughput FrankenPHP worker environment this means repeated parse overhead for what should be a cached path. Dynamic includes also hide the dependency graph from static analysis tools.

Static expressions — string literals, `__DIR__`, `__FILE__`, and concatenations thereof — are explicitly allowed because OPcache resolves them at compile time.

## What It Detects

`include`, `include_once`, `require`, and `require_once` statements where the path expression is:
- A variable: `require $path;`
- A function call: `require getPath();`
- Any expression that is not a string literal, `__DIR__`, `__FILE__`, or a concatenation of those.

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to the flagged `require`/`include` statement.

Project-configured comment:
```
// TODO: [opcache] dynamic include prevents OPcache optimization
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | `'TODO: Dynamic include prevents compile-time optimization'` | Comment text to prepend |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidDynamicIncludeRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [opcache] dynamic include prevents OPcache optimization',
]);
```

## Example

### Before
```php
$path = 'foo.php';
require $path;
```

### After
```php
$path = 'foo.php';
// TODO: Dynamic include prevents compile-time optimization
require $path;
```

**Static includes are not flagged:**
```php
require __DIR__ . '/foo.php';
```

## Resolution

When you see the TODO comment from this rule:
1. Replace the dynamic path with a hardcoded string literal or a `__DIR__ . '/relative/path.php'` expression.
2. If the file to include is determined at runtime (e.g., a plugin system), consider replacing the dynamic include pattern with an explicit class map or autoloader registration instead.
3. If the include is inside a bootstrap file that genuinely must be dynamic, document why and, if possible, move the dynamic resolution to a preload script so OPcache can handle it.

## Related Rules

- [`ForbidVariableVariablesRector`](ForbidVariableVariablesRector.md) — flags `$$var` variable variables that also prevent compile-time resolution
- [`ForbidGlobalKeywordRector`](ForbidGlobalKeywordRector.md) — flags `global` keyword usage that degrades OPcache scope-level optimization
