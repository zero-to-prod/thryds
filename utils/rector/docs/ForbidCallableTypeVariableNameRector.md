# ForbidCallableTypeVariableNameRector

Flag local variables named after a PHP callable type (e.g. `$Closure`, `$Callback`) with a TODO comment to rename them to something that describes their behaviour.

**Category:** Naming
**Mode:** `warn` (warn-only; `auto` mode is a no-op)
**Auto-fix:** No

## Rationale

A variable named `$Closure` or `$Callback` describes the _kind_ of value it holds, not what it _does_. The project convention is that object variables are named after their type — so `$Closure` would be the correct name for any closure under `RenameVarToMatchReturnTypeRector`. This rule carves out an exception: callable-type names are too generic and must be replaced by a name that communicates intent, e.g. `$render_error` or `$on_submit`. The rule is warn-only because Rector cannot infer the correct behavioural name automatically.

The comment is generated using `sprintf($message, $varName)` so the variable name appears in the output.

## What It Detects

An assignment statement `$VarName = <callable expr>` where `$VarName` is in the configured `forbiddenNames` list (by default: `Closure`, `Callable`, `Callback`, `Function`, `Func`).

## Transformation

### In `warn` mode
Adds a `// TODO: [ForbidCallableTypeVariableNameRector] rename $<name> to describe its behaviour` comment above the assignment (idempotent, keyed on the prefix before `%s`).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `forbiddenNames` | `string[]` | `[]` | Variable names to flag |
| `mode` | `string` | `'warn'` | Only `'warn'` has effect; `'auto'` is a no-op |
| `message` | `string` | `'TODO: Rename $%s to describe its behaviour'` | `sprintf`-formatted comment; `%s` is replaced with the variable name |

Project config (`rector.php`): `forbiddenNames => ['Closure', 'Callable', 'Callback', 'Function', 'Func']`, `message => 'TODO: [ForbidCallableTypeVariableNameRector] rename $%s to describe its behaviour'`.

## Example

### Before
```php
$Closure = static function (): void {};
```

### After
```php
// TODO: Rename $Closure to describe its behaviour
$Closure = static function (): void {};
```

## Resolution

When you see the TODO comment from this rule:
1. Determine what the closure _does_ (e.g. renders an error, handles a submit).
2. Rename the variable to a snake_case description: `$render_error`, `$handle_submit`.
3. Update all usages of the old variable name in the same scope.
4. Remove the TODO comment.

## Related Rules

- [`RenameVarToMatchReturnTypeRector`](RenameVarToMatchReturnTypeRector.md) — renames variables to match their return type; this rule overrides that convention for callable types
- [`RenamePrimitiveVarToSnakeCaseRector`](RenamePrimitiveVarToSnakeCaseRector.md) — the target name after resolution should be snake_case
