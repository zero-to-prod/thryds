# ForbidExitInSourceRector

Removes or flags `exit()` and `die()` statements from source code.

**Category:** Forbidden Constructs
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (in `auto` mode — removes the statement entirely)

## Rationale

`exit()` and `die()` terminate the PHP process immediately, bypassing all shutdown handlers, destructors, and framework teardown logic. In a FrankenPHP worker, this kills the worker process rather than just ending a single request — meaning the next request has no worker to serve it until FrankenPHP spawns a replacement. This creates hard-to-reproduce latency spikes and can corrupt in-memory state that should have been cleaned up. Application code must signal errors through exceptions or structured return values, never by halting the process.

## What It Detects

Expression statements whose expression is an `exit` or `die` construct (PHP treats both as the same `Exit_` AST node).

## Transformation

### In `auto` mode

The entire `exit(...)` or `die(...)` expression statement is removed from the AST.

### In `warn` mode

A `// TODO` comment with the configured `message` is prepended to the statement.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to remove the statement; `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text prepended in `warn` mode |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidExitInSourceRector::class, [
    'mode' => 'auto',
]);
```

## Example

### Before (`exit`)
```php
$value = 'test';
exit(1);
echo $value;
```

### After (`exit`)
```php
$value = 'test';
echo $value;
```

### Before (`die`)
```php
$value = 'test';
die('error');
echo $value;
```

### After (`die`)
```php
$value = 'test';
echo $value;
```

## Resolution

When you see the TODO comment from this rule (warn mode):
1. Replace `exit()` / `die()` used for error signalling with a thrown exception.
2. Replace `exit()` used at a script entry point (e.g., a CLI script) with a proper return code propagated through the call stack.
3. If the intent was to halt after sending an HTTP response, return a PSR-7 response object from the controller instead.

## Related Rules

- [`ForbidEvalRector`](ForbidEvalRector.md) — removes `eval()` statements
- [`ForbiddenFuncCallRector`](ForbiddenFuncCallRector.md) — removes calls to other forbidden functions such as `error_log`, `extract`, `compact`, and `session_start`
