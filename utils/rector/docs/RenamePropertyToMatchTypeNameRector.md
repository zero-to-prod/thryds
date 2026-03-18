# RenamePropertyToMatchTypeNameRector

Rename class properties typed with a class or enum to exactly match the short name of that type.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The project convention is that object-typed properties are named after their type. `public AppEnv $appEnv` creates a redundant, lowercase-first alias for `AppEnv`. Renaming to `public AppEnv $AppEnv` makes the property name and the type name identical, so there is only one name to remember. This also enables `AddNamedArgWhenVarMismatchesParamRector` to detect when `$this->AppEnv` is passed to a method: if the parameter is also named `$AppEnv`, no named-argument label is needed.

The rule also renames any co-located class constant whose name and string value both match the old property name (e.g. `const string appEnv = 'appEnv'`), and updates `@see` doc-comment references. All `$this->prop` accesses inside the class body are updated together.

## What It Detects

A class property whose type is a named class or interface (`Name` node, not a primitive `Identifier`), and whose property name differs from the type's short name, e.g. `public AppEnv $appEnv`. External `$obj->propName` accesses are also detected when the underlying property's native type is a class type.

## Transformation

### In `auto` mode
Renames the property declaration, any matching class constant (name and value must both equal the old property name), `$this->prop` fetches inside the class, and external property fetches on typed objects.

### In `warn` mode
Adds the configured `message` as a `//` comment above the class declaration (idempotent).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `mode => 'auto'`.

## Example

### Before
```php
class Config
{
    public AppEnv $appEnv;

    public function isProduction(): bool
    {
        return $this->appEnv instanceof AppEnv;
    }
}
```

### After
```php
class Config
{
    public AppEnv $AppEnv;

    public function isProduction(): bool
    {
        return $this->AppEnv instanceof AppEnv;
    }
}
```

### Before (with matching class constant)
```php
class Config
{
    /** @see $appEnv */
    public const string appEnv = 'appEnv';
    public AppEnv $appEnv;
}
```

### After
```php
class Config
{
    /** @see $AppEnv */
    public const string AppEnv = 'AppEnv';
    public AppEnv $AppEnv;
}
```

## Related Rules

- [`RenamePrimitivePropertyToSnakeCaseRector`](RenamePrimitivePropertyToSnakeCaseRector.md) — the counterpart rule for primitive-typed properties (snake_case)
- [`RenameParamToMatchTypeNameRector`](RenameParamToMatchTypeNameRector.md) — applies the same naming convention to function/method parameters
- [`RenameVarToMatchReturnTypeRector`](RenameVarToMatchReturnTypeRector.md) — applies the same convention to local variables
