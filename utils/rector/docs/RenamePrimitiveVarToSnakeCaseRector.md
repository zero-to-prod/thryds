# RenamePrimitiveVarToSnakeCaseRector

Rename local variables holding primitive values from camelCase to snake_case.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The project distinguishes object variables (PascalCase, named after their type) from primitive variables (snake_case). A local variable like `$myString` or `$itemCount` looks like it could be an object reference when cased as camelCase. Enforcing snake_case for primitives makes the distinction structural: any PascalCase variable is an object, any snake_case variable is a raw value. This makes code scannable at a glance without requiring type annotations or IDE support.

The rule propagates every rename to all subsequent statements in the same block, including right-hand sides of later assignments, and resolves PHPStan type information before applying prior renames to avoid stale scope data.

## What It Detects

An assignment `$camelCaseVar = <expr>` where PHPStan resolves the right-hand side as a scalar, array, or null type (not an object), and the variable name is not already snake_case.

## Transformation

### In `auto` mode
Renames the variable at the assignment and propagates the rename to all subsequent usages in the same block.

### In `warn` mode
Adds the configured `message` as a `//` comment above the enclosing block node (idempotent).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `mode => 'auto'`.

## Example

### Before
```php
$myString = 'hello';
$itemCount = 42;
$isActive = true;
$priceValue = 3.14;
$tagList = ['a', 'b'];
```

### After
```php
$my_string = 'hello';
$item_count = 42;
$is_active = true;
$price_value = 3.14;
$tag_list = ['a', 'b'];
```

### Before (rename propagates to subsequent usages)
```php
$userName = 'Alice';
$greeting = 'Hello, ' . $userName;
echo $greeting;
echo $userName;
```

### After
```php
$user_name = 'Alice';
$greeting = 'Hello, ' . $user_name;
echo $greeting;
echo $user_name;
```

## Related Rules

- [`RenamePrimitivePropertyToSnakeCaseRector`](RenamePrimitivePropertyToSnakeCaseRector.md) — applies the same snake_case convention to class properties
- [`RenameVarToMatchReturnTypeRector`](RenameVarToMatchReturnTypeRector.md) — renames variables holding objects to match their type name (PascalCase)
