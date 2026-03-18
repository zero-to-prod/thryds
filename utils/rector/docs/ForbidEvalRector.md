# ForbidEvalRector

Removes or flags `eval()` statements from source code.

**Category:** Forbidden Constructs
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (in `auto` mode — removes the statement entirely)

## Rationale

`eval()` executes an arbitrary string as PHP code at runtime. It prevents static analysis, makes code impossible to reason about, creates security vulnerabilities when the evaluated string includes any user-controlled data, and forces the PHP engine to parse and compile a new code unit on every call. OPcache cannot cache the evaluated string. In a FrankenPHP worker context, `eval()` also risks corrupting persistent state across requests. There is no legitimate use case in application code.

## What It Detects

Expression statements whose expression is an `eval()` construct.

## Transformation

### In `auto` mode

The entire `eval(...)` expression statement is removed from the AST.

### In `warn` mode

A `// TODO` comment with the configured `message` is prepended to the statement.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to remove the statement; `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text prepended in `warn` mode |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidEvalRector::class, [
    'mode' => 'auto',
]);
```

## Example

### Before
```php
$value = 'test';
eval('echo "hello";');
echo $value;
```

### After
```php
$value = 'test';
echo $value;
```

## Resolution

When you see the TODO comment from this rule (warn mode):
1. Identify what the `eval()` call was computing and replace it with explicit PHP logic.
2. If the intent was dynamic dispatch, use a strategy pattern, a lookup table of callables, or a match expression instead.
3. If the intent was code generation, move that to a compile-time step (e.g., a Rector rule or a build script).

## Related Rules

- [`ForbiddenFuncCallRector`](ForbiddenFuncCallRector.md) — removes calls to other forbidden functions such as `error_log`, `extract`, `compact`, and `session_start`
- [`ForbidExitInSourceRector`](ForbidExitInSourceRector.md) — removes `exit()` and `die()` statements
