# ForbidErrorSuppressionRector

Flags use of the `@` error suppression operator, which adds per-call runtime overhead and hides failures.

**Category:** OPcache Optimization
**Mode:** `warn`
**Auto-fix:** No

## Rationale

The `@` operator works by installing a temporary error handler before the call, executing the expression, then restoring the previous handler afterward. This install/restore cycle happens on every single call at runtime — it is not a compile-time hint. OPcache cannot optimise this pattern away because the handler swap is a side-effectful runtime operation. The overhead is measurable under load and compounds when `@` is used inside loops or on hot paths.

Beyond performance, `@` silently discards errors that should be handled explicitly. A failed `file_get_contents`, for example, returns `false` — code that suppresses the error and doesn't check the return value will then pass `false` to the next operation, producing confusing downstream failures rather than a clear error at the source.

## What It Detects

Any expression statement that contains an `ErrorSuppress` node anywhere in its subtree — i.e., any use of the `@` prefix operator on any sub-expression.

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to the statement containing the `@` operator.

Project-configured comment:
```
// TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | `'TODO: Error suppression adds per-call overhead — handle errors explicitly'` | Comment text to prepend |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidErrorSuppressionRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly',
]);
```

## Example

### Before
```php
@file_get_contents('missing.txt');
```

### After
```php
// TODO: Error suppression adds per-call overhead — handle errors explicitly
@file_get_contents('missing.txt');
```

## Resolution

When you see the TODO comment from this rule:
1. Remove the `@` prefix.
2. Check the return value explicitly: `$result = file_get_contents('missing.txt'); if ($result === false) { ... }`.
3. For functions that can throw, wrap in a try/catch rather than suppressing.
4. If the function is a legacy C extension that only signals errors through the error system (no return value check possible), consider wrapping it in a thin adapter that converts errors to exceptions using `set_error_handler`.

## Related Rules

- [`ForbidVariableVariablesRector`](ForbidVariableVariablesRector.md) — flags variable variables that prevent compile-time variable resolution
- [`ForbidGlobalKeywordRector`](ForbidGlobalKeywordRector.md) — flags `global` keyword usage that degrades OPcache scope-level optimization
- [`ForbidDynamicIncludeRector`](ForbidDynamicIncludeRector.md) — flags dynamic include/require paths that prevent OPcache optimization
