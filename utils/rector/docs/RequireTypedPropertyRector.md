# RequireTypedPropertyRector

Flag untyped class properties with a TODO comment to add a type declaration, improving OPcache memory layout optimization.

**Category:** Type Safety
**Mode:** `warn` (warn-only; `auto` mode is a no-op)
**Auto-fix:** No

## Rationale

PHP's OPcache uses class property type information to optimize memory layout at compile time. When a property has no declared type, OPcache must allocate a generic `zval` slot that can hold any value, missing the opportunity to use a more compact representation. Typed properties also allow PHPStan to track the property's type across all methods without requiring docblocks. This rule surfaces untyped properties so they can be manually typed, since Rector cannot safely infer the correct type without full dataflow analysis.

## What It Detects

Any class property declaration (`Property` node) that has no type annotation (`$node->type === null`).

## Transformation

### In `warn` mode
Adds `// TODO: Add a type declaration to improve optimization` (or the configured `message`) above the property declaration (idempotent — not re-added if the comment is already present).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | Only `'warn'` has effect; `'auto'` is a no-op |
| `message` | `string` | `'TODO: Add a type declaration to improve optimization'` | Comment text to add |

Project config (`rector.php`): `mode => 'warn'`, `message => 'TODO: [opcache] add a type declaration to improve OPcache optimization'`.

## Example

### Before
```php
class User
{
    public $name;
}
```

### After
```php
class User
{
    // TODO: Add a type declaration to improve optimization
    public $name;
}
```

## Resolution

When you see the TODO comment from this rule:
1. Determine the type(s) the property holds across all assignments in the class.
2. Add the appropriate type declaration: `public string $name;`, `public ?Router $Router;`, etc.
3. If the property is assigned values of multiple unrelated types, refactor the class to use a single type.
4. Remove the TODO comment once the type is declared.

## Related Rules

- [`RequireReturnTypeRector`](RequireReturnTypeRector.md) — enforces typed return values
- [`RequireParamTypeRector`](RequireParamTypeRector.md) — enforces typed parameters
- [`RenamePropertyToMatchTypeNameRector`](RenamePropertyToMatchTypeNameRector.md) — once a property is typed with an object type, this rule ensures the property name matches the type
- [`RenamePrimitivePropertyToSnakeCaseRector`](RenamePrimitivePropertyToSnakeCaseRector.md) — once a property is typed with a primitive, this rule ensures the name is snake_case
