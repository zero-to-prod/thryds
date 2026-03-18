# ForbiddenFuncCallRector

Removes or flags calls to a configurable list of forbidden PHP functions.

**Category:** Forbidden Constructs
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (in `auto` mode — removes the statement entirely)

## Rationale

The project forbids four functions, each for a distinct reason:

- **`error_log`**: Writes to the PHP error log directly, bypassing the structured `Log` class. This produces unstructured output that has no request ID, no log level, and no event label — making log aggregation and alerting impossible.
- **`extract`**: Imports arbitrary keys from an array into the local symbol table. This makes variable provenance invisible to static analysis, IDE tooling, and to any human reader. It is a vector for variable injection bugs.
- **`compact`**: The inverse of `extract` — silently packs named local variables into an array. It couples variable names to data structures implicitly and breaks when variables are renamed.
- **`session_start`**: This application manages state without PHP sessions. Calling `session_start` would introduce per-request file locking and a stateful model that conflicts with the FrankenPHP worker execution model.

In `auto` mode the entire expression statement is deleted. In `warn` mode a TODO comment is prepended instead.

## What It Detects

Top-level expression statements whose expression is a function call matching any name in the configured `functions` list.

## Transformation

### In `auto` mode

The entire expression statement is removed from the AST.

### In `warn` mode

A `// TODO` comment with the configured `message` is prepended to the statement.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to remove the call; `'warn'` to add a TODO comment |
| `functions` | `string[]` | `[]` | Function names to forbid. Also accepted as the top-level configuration array for backward compatibility. |
| `message` | `string` | `''` | Comment text prepended in `warn` mode |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbiddenFuncCallRector::class, [
    'functions' => [
        'error_log',
        'extract',
        'compact',
        'session_start',
    ],
    'mode' => 'auto',
]);
```

## Example

### Before
```php
$value = 'test';
error_log('debug info');
echo $value;
```

### After
```php
$value = 'test';
echo $value;
```

## Resolution

When you see the TODO comment from this rule (warn mode):
1. For `error_log`: Replace with a structured `Log::*` call including an `event` key and any relevant context.
2. For `extract`: Assign each key individually with explicit variable names: `$foo = $data['foo'];`.
3. For `compact`: Build the array explicitly: `['foo' => $foo, 'bar' => $bar]`.
4. For `session_start`: Remove session usage entirely; use the application's own state management instead.

## Related Rules

- [`ForbidEvalRector`](ForbidEvalRector.md) — removes `eval()` statements
- [`ForbidExitInSourceRector`](ForbidExitInSourceRector.md) — removes `exit()` and `die()` statements
