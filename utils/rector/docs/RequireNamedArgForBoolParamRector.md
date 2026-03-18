# RequireNamedArgForBoolParamRector

Add named argument labels to call sites that pass boolean literals as positional arguments, and cascade labels to all subsequent positional arguments.

**Category:** Type Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (when reflection is available); adds TODO comment when it is not

## Rationale

A bare `true` or `false` in a function call is a semantic black box: `query($sql, true, 100)` gives no indication what `true` means. Named arguments make the intent explicit — `query($sql, buffered: true, timeout: 100)` — and document the parameter's role at the call site without requiring the reader to look up the signature. Because PHP requires that positional arguments precede all named ones, when a boolean is named, all subsequent positional arguments must also be named; the rule handles this cascade automatically.

When reflection is unavailable (e.g. the function is not yet defined), a `// TODO: Add named argument for boolean literal` comment is added instead.

## What It Detects

A call (`FuncCall`, `MethodCall`, or `StaticCall`) that passes a `true` or `false` literal as a positional (non-named) argument, provided there are at least 2 arguments total (configurable via `skipWhenOnlyArg`).

## Transformation

### In `auto` mode
Names the argument at the boolean position and all subsequent positional arguments using the parameter names from reflection. If reflection is unavailable or a variadic parameter is encountered, falls back to adding a TODO comment.

### In `warn` mode
Adds the configured `message` as a `//` comment above the call (idempotent) instead of rewriting.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `skipBuiltinFunctions` | `bool` | `false` | Skip native PHP functions |
| `skipWhenOnlyArg` | `bool` | `true` | Skip calls where the boolean is the only argument |
| `todoMessage` | `string` | `'TODO: Add named argument for boolean literal'` | Comment text when reflection is unavailable |
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `skipBuiltinFunctions => false`, `skipWhenOnlyArg => true`, `mode => 'auto'`.

## Example

### Before
```php
$value = 'data';
setCacheEntry('key', $value, true);
```

### After
```php
$value = 'data';
setCacheEntry('key', $value, compress: true);
```

### Before (cascade — bool at position 1 causes position 2 to also be named)
```php
$db->query($sql, true, 100);
```

### After
```php
$db->query($sql, buffered: true, timeout: 100);
```

## Resolution

When you see `// TODO: Add named argument for boolean literal`:
1. Look up the function or method signature to find the parameter name at the boolean's position.
2. Add the named label: `compress: true`, `buffered: false`, etc.
3. Name all subsequent positional arguments in the same call to satisfy PHP's positional-before-named constraint.
4. Remove the TODO comment.

## Related Rules

- [`AddNamedArgWhenVarMismatchesParamRector`](AddNamedArgWhenVarMismatchesParamRector.md) — adds named labels when a variable name differs from the parameter name (complementary coverage for non-boolean args)
