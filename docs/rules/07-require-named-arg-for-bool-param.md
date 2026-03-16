# RequireNamedArgForBoolParamRector

## Tool

Rector (custom rule)

## What it does

Automatically adds named argument labels to any function or method call that passes a
boolean literal (`true` or `false`) as a positional argument. Resolves the parameter
name from the callee's signature via reflection. When reflection fails, adds a TODO
comment instead.

## Why it matters

`$emitter->emit(true, false)` is opaque — an agent cannot determine what those booleans
control without reading the function signature. `$emitter->emit(flush: true, close: false)`
is self-documenting at every call site.

Boolean literals are uniquely bad because they carry zero semantic information. A string
or object argument at least hints at its purpose through its value.

## Refactoring strategy

### Auto-fix: resolve param name and add label

```php
// before
$cache->set('key', $value, true);
json_encode($data, JSON_THROW_ON_ERROR, true);
$service->process($order, false, true);

// after
$cache->set('key', $value, compress: true);
json_encode($data, JSON_THROW_ON_ERROR, assoc: true);
$service->process($order, dryRun: false, notify: true);
```

### Named arg cascading

Once a named argument is inserted, all subsequent positional arguments must also be
named (PHP syntax requirement). The rule automatically labels all remaining positional
arguments after the first bool insertion.

```php
// before
$db->query($sql, true, 100);

// after — `true` gets a label, and `100` must also be labeled
$db->query($sql, buffered: true, timeout: 100);
```

### When reflection fails (fall back to TODO)

- Dynamic method calls (`$obj->$method(true)`) — callee unknown.
- Calls to functions/methods in unloaded or external code.
- Variadic parameters — named args cannot target variadic params.

```php
// TODO: Add named argument for boolean literal
$handler->$action(true, $payload);
```

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `skipBuiltinFunctions` | `bool` | `false` | Skip calls to PHP built-in functions |
| `skipWhenOnlyArg` | `bool` | `true` | Skip when the boolean is the only argument |
| `todoMessage` | `string` | `'TODO: Add named argument for boolean literal'` | Comment when reflection fails |

### Example rector.php

```php
use Utils\Rector\Rector\RequireNamedArgForBoolParamRector;

$rectorConfig->ruleWithConfiguration(RequireNamedArgForBoolParamRector::class, [
    'skipBuiltinFunctions' => false,
    'skipWhenOnlyArg' => true,
]);
```

## Implementation notes

- **Node types**: `FuncCall`, `MethodCall`, `StaticCall`
- **Detection**: Iterate `$node->args`. For each `Arg` where:
  1. `$arg->name === null` (positional)
  2. `$arg->value` is `ConstFetch` with name `true` or `false`
- **Resolution**: Use `ReflectionResolver::resolveFunctionLikeReflectionFromCall()` to
  get the callee's parameter list. Map argument position to parameter name. If
  reflection returns null, fall back to TODO.
- **Cascading**: After inserting a named arg, iterate all subsequent `Arg` nodes in the
  call. For each positional arg, resolve its param name and add the label. If any
  subsequent resolution fails, abort the entire call (do not partially name).
- **Variadic check**: If the resolved parameter is variadic, skip this argument.
- **Skip logic**: When `skipBuiltinFunctions` is true, check if the function name
  resolves to a built-in via reflection. When `skipWhenOnlyArg` is true, check
  `count($node->args) === 1`.
- Implements `ConfigurableRectorInterface`
