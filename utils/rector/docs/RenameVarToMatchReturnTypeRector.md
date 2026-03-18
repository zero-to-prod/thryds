# RenameVarToMatchReturnTypeRector

Rename local variables to exactly match the short name of the class returned by their assigned expression.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

When a call returns a typed object, the variable holding it should be named after that type. `$result = $dispatcher->dispatch()` obscures what `$result` actually holds; `$DispatchResult = $dispatcher->dispatch()` makes the type visible at the assignment site without requiring a type annotation or IDE hover. This also satisfies `AddNamedArgWhenVarMismatchesParamRector` downstream: if `$DispatchResult` is later passed to a parameter named `$DispatchResult`, no named-argument label is needed.

The rule renames all subsequent usages of the variable, including on the right-hand sides of later assignments, resolving type information _before_ applying prior renames to avoid PHPStan scope confusion.

## What It Detects

An assignment `$foo = <expr>` where `<expr>` has a known single-class return type and the current variable name differs from that type's short name, e.g. `$result = $dispatcher->dispatch()` when `dispatch()` returns `DispatchResult`.

Names in the `skipNames` list are never the target of a rename.

## Transformation

### In `auto` mode
Renames the assignment target and propagates the rename to all subsequent statements in the same block.

### In `warn` mode
Adds the configured `message` as a `//` comment above the assignment statement (idempotent).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `skipNames` | `string[]` | `[]` | Type short-names to never rename to (e.g. `['Closure']`) |
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `skipNames => ['Closure']`, `mode => 'auto'`.

## Example

### Before
```php
$dispatcher = new RequestDispatcher();
$result = $dispatcher->dispatch();
echo $result;
```

### After
```php
$RequestDispatcher = new RequestDispatcher();
$DispatchResult = $RequestDispatcher->dispatch();
echo $DispatchResult;
```

### Before (`new` expression)
```php
$emitter = new HtmlEmitter();
echo $emitter;
```

### After
```php
$HtmlEmitter = new HtmlEmitter();
echo $HtmlEmitter;
```

## Related Rules

- [`RenameParamToMatchTypeNameRector`](RenameParamToMatchTypeNameRector.md) — applies the same convention to parameters
- [`RenamePropertyToMatchTypeNameRector`](RenamePropertyToMatchTypeNameRector.md) — applies the same convention to class properties
- [`AddNamedArgWhenVarMismatchesParamRector`](AddNamedArgWhenVarMismatchesParamRector.md) — adds named labels at call sites where variable names differ from parameter names
