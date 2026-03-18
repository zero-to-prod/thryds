# ForbidVariableVariablesRector

Flags variable variables (`$$var`) that prevent OPcache from resolving variable names at compile time.

**Category:** OPcache Optimization
**Mode:** `warn`
**Auto-fix:** No

## Rationale

OPcache compiles PHP functions and methods to bytecode and assigns each local variable a fixed numeric slot at compile time. This slot-based lookup is fast because no hash map traversal is needed at runtime. Variable variables (`$$name`) break this model: the variable name is only known at runtime, so OPcache cannot assign static slots. The PHP engine must fall back to a runtime symbol table lookup for every access to a variable variable, which is slower and prevents a range of bytecode-level optimizations. Variable variables also make code significantly harder to trace statically — IDEs, type checkers, and other analysis tools cannot follow the indirection.

## What It Detects

Any statement that contains a variable variable: a `Variable` AST node whose `name` property is itself a `Node` (i.e., a runtime expression) rather than a plain string.

Examples: `$$name`, `$$this->key`, `$${'dynamic'}`.

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to the statement containing the variable variable.

Project-configured comment:
```
// TODO: [opcache] variable variables prevent compile-time variable resolution
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | `'TODO: Variable variables prevent compile-time variable resolution'` | Comment text to prepend |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidVariableVariablesRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [opcache] variable variables prevent compile-time variable resolution',
]);
```

## Example

### Before
```php
$$name = 'value';
```

### After
```php
// TODO: Variable variables prevent compile-time variable resolution
$$name = 'value';
```

## Resolution

When you see the TODO comment from this rule:
1. Enumerate the finite set of variable names being accessed dynamically and replace with an explicit `match` or `if`/`elseif` chain.
2. If the intent is a dynamic property bag, use an explicit associative array or a typed data class instead: `$data[$name]` rather than `$$name`.
3. If the code is calling `extract()`, remove `extract()` and reference the array keys directly (also covered by [`ForbiddenFuncCallRector`](ForbiddenFuncCallRector.md)).

## Related Rules

- [`ForbidDynamicIncludeRector`](ForbidDynamicIncludeRector.md) — flags dynamic include/require paths that also prevent OPcache optimization
- [`ForbidGlobalKeywordRector`](ForbidGlobalKeywordRector.md) — flags `global` keyword usage that degrades OPcache scope-level optimization
- [`ForbidErrorSuppressionRector`](ForbidErrorSuppressionRector.md) — flags `@` error suppression that adds per-call overhead
