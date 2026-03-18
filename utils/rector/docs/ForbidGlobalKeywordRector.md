# ForbidGlobalKeywordRector

Flags use of the `global` keyword, which prevents OPcache from applying scope-level variable optimizations.

**Category:** OPcache Optimization
**Mode:** `warn`
**Auto-fix:** No

## Rationale

OPcache assigns each function or method scope a fixed set of local variable slots at compile time, identified by position rather than name. This makes variable access O(1) without any hash-map lookup. When a function declares `global $var`, it tells the engine that `$var` is actually a reference into the global symbol table (`$GLOBALS`). OPcache cannot pre-assign a static slot for a global-aliased variable because the binding is dynamic — it depends on what is in `$GLOBALS` at runtime. The entire scope loses the opportunity for slot-based optimization for those variables.

Beyond performance, `global` is an implicit coupling between a function and mutable global state, making functions hard to test, hard to reason about, and unsafe in concurrent or persistent-worker contexts like FrankenPHP where global state persists across requests.

## What It Detects

Any `global` statement (`Global_` AST node) anywhere in the codebase.

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to the `global` statement.

Project-configured comment:
```
// TODO: [opcache] global keyword prevents scope-level optimization
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | `'TODO: Global keyword prevents scope-level optimization'` | Comment text to prepend |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidGlobalKeywordRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [opcache] global keyword prevents scope-level optimization',
]);
```

## Example

### Before
```php
function foo(): void
{
    global $config;
}
```

### After
```php
function foo(): void
{
    // TODO: Global keyword prevents scope-level optimization
    global $config;
}
```

## Resolution

When you see the TODO comment from this rule:
1. Pass the previously-global value as an explicit function parameter instead.
2. If the global is a shared service or configuration object, inject it through a constructor or a service container rather than accessing it via `global`.
3. In FrankenPHP worker mode especially, ensure the replacement does not hold mutable shared state across requests — use `RequestId::reset()` as the model for per-request state resets.

## Related Rules

- [`ForbidVariableVariablesRector`](ForbidVariableVariablesRector.md) — flags variable variables that prevent compile-time variable resolution
- [`ForbidDynamicIncludeRector`](ForbidDynamicIncludeRector.md) — flags dynamic include/require paths that prevent OPcache optimization
- [`ForbidErrorSuppressionRector`](ForbidErrorSuppressionRector.md) — flags `@` error suppression that adds per-call overhead
