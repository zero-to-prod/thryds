# RenameParamToMatchTypeNameRector

Rename function and method parameters to exactly match the short name of their declared type.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The project convention is that object-typed variables are named after their type (PascalCase for objects). When a parameter like `$r` is typed `Router`, the name `$r` is an abbreviation that obscures the type and breaks the naming convention. Renaming to `$Router` creates a direct, machine-verifiable link between the type and the identifier: the variable name _is_ the type name. This also feeds `AddNamedArgWhenVarMismatchesParamRector` — once all parameters follow the convention, call sites that pass a same-named variable need no named-argument label.

All usages of the parameter inside the function body are renamed together with the declaration.

## What It Detects

A parameter whose name differs from the short name of its object (or interface) type, e.g. `function handle(Router $r)` where the expected name is `$Router`. Nullable types (`?Router`) are handled by unwrapping the inner type. Variadic parameters are skipped.

## Transformation

### In `auto` mode
Renames the parameter declaration and every reference to that variable inside the function/method/closure body.

### In `warn` mode
Adds the configured `message` as a `//` comment above the function/method node (idempotent).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `mode => 'auto'`.

## Example

### Before
```php
function handle(Router $r): void
{
    $r->dispatch();
}
```

### After
```php
function handle(Router $Router): void
{
    $Router->dispatch();
}
```

### Before (nullable type)
```php
function handle(?Router $r): void
{
    if ($r !== null) {
        $r->dispatch();
    }
}
```

### After
```php
function handle(?Router $Router): void
{
    if ($Router !== null) {
        $Router->dispatch();
    }
}
```

## Related Rules

- [`RenameVarToMatchReturnTypeRector`](RenameVarToMatchReturnTypeRector.md) — applies the same convention to local variables assigned from call return values
- [`RenamePropertyToMatchTypeNameRector`](RenamePropertyToMatchTypeNameRector.md) — applies the same convention to class properties
- [`AddNamedArgWhenVarMismatchesParamRector`](AddNamedArgWhenVarMismatchesParamRector.md) — adds named labels at call sites where variable names differ from parameter names
