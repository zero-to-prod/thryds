# RenamePrimitivePropertyToSnakeCaseRector

Rename primitive-typed class properties from camelCase to snake_case, and update all internal usages.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The project uses two complementary naming conventions: object-typed properties are named after their type (PascalCase), while primitive-typed properties — `string`, `int`, `bool`, `float`, `array`, and nullable variants — use snake_case. This distinction makes the casing itself a signal: PascalCase identifiers refer to objects, snake_case identifiers hold raw values. Without enforcement, `$bladeCacheDir` (a string) looks the same as an object property, erasing that useful distinction.

The rule also renames any co-located class constant whose name and string value both match the old property name (the `const string bladeCacheDir = 'bladeCacheDir'` pattern used for string-keyed property access), keeping the constant in sync. Doc-comment `@see` references are updated as well.

## What It Detects

A class property whose type is a primitive (`string`, `int`, `bool`, `float`, `array`, or a nullable of these, resolved via `Identifier`) whose name is not already snake_case. External `$obj->camelCaseProp` access is also detected when the property's native type is primitive.

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
    public string $bladeCacheDir;
    public int $statusCode;

    public function getDirs(): string
    {
        return $this->bladeCacheDir;
    }
}
```

### After
```php
class Config
{
    public string $blade_cache_dir;
    public int $status_code;

    public function getDirs(): string
    {
        return $this->blade_cache_dir;
    }
}
```

### Before (with matching class constant)
```php
class Config
{
    /** @see $bladeCacheDir */
    public const string bladeCacheDir = 'bladeCacheDir';
    public string $bladeCacheDir;
}
```

### After
```php
class Config
{
    /** @see $blade_cache_dir */
    public const string blade_cache_dir = 'blade_cache_dir';
    public string $blade_cache_dir;
}
```

## Related Rules

- [`RenamePropertyToMatchTypeNameRector`](RenamePropertyToMatchTypeNameRector.md) — the counterpart rule for object-typed properties (PascalCase = type name)
- [`RenamePrimitiveVarToSnakeCaseRector`](RenamePrimitiveVarToSnakeCaseRector.md) — applies the same snake_case convention to local variables holding primitives
