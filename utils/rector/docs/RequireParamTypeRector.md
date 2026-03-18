# RequireParamTypeRector

Add type declarations to untyped function and method parameters by inferring from default values, docblocks, and constructor property assignments, or add a per-parameter TODO comment when inference fails.

**Category:** Type Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (when type is inferrable); adds TODO comment when it is not

## Rationale

Untyped parameters prevent PHPStan from propagating type information into function bodies, and prevent OPcache from building accurate function call type maps for JIT optimization. In many cases the type is already implied by a default value (`$page = 1` → `int`) or a `@param` docblock. This rule makes those implicit types explicit, improving both analysis accuracy and runtime performance. For parameters that genuinely cannot be inferred, it emits a per-parameter TODO comment so they can be addressed manually.

## What It Detects

Any parameter without a type declaration in a function, method, closure, or arrow function (subject to `skipVariadic` and `skipClosures` settings). Inference is attempted in order: (1) default value literal, (2) `@param` docblock, (3) typed property assignment in the constructor body.

## Transformation

### In `auto` mode
- **Default value**: `$page = 1` → `int $page = 1`; `$name = 'world'` → `string $name = 'world'`; `$enabled = false` → `bool $enabled = false`; `$items = []` → `array $items = []`.
- **Docblock `@param`**: reads the `@param` annotation and maps it to a PHP type node (supports nullable and union types).
- **Constructor property assignment**: if `$this->name = $name` and the property `$name` has a type, uses that type.
- **Unresolvable**: adds `// TODO: Add param type for $<name>` above the function/method (one comment per unresolvable parameter, idempotent).

### In `warn` mode
Adds TODO comments for unresolvable parameters only (same behaviour as `auto` for the unresolvable case; inferred types are still added).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `skipVariadic` | `bool` | `true` | Skip variadic `...$args` parameters |
| `skipClosures` | `bool` | `false` | Skip `Closure` and arrow function nodes |
| `useDocblocks` | `bool` | `true` | Allow inference from `@param` docblocks |
| `mode` | `string` | `'warn'` | `'auto'` to infer and patch; `'warn'` to add TODO when unresolvable |
| `message` | `string` | `'TODO: Add param type'` | Prefix for per-parameter TODO comments |

Project config (`rector.php`): `skipVariadic => true`, `useDocblocks => true`, `mode => 'auto'`.

## Example

### Before (inferred from default)
```php
function toggle($enabled = false) {
    return $enabled;
}
```

### After
```php
function toggle(bool $enabled = false) {
    return $enabled;
}
```

### Before (inferred from docblock)
```php
/** @param string $name */
function greet($name) {
    return "Hello, $name";
}
```

### After
```php
/** @param string $name */
function greet(string $name) {
    return "Hello, $name";
}
```

### Before (unresolvable)
```php
function process($data) {
    return $data;
}
```

### After
```php
// TODO: Add param type for $data
function process($data) {
    return $data;
}
```

## Resolution

When you see `// TODO: Add param type for $<name>`:
1. Determine the actual type(s) the parameter accepts at all call sites.
2. Add the explicit type declaration: `string`, `int`, `ResponseInterface`, etc.
3. If the parameter accepts multiple unrelated types, consider overloading via separate methods or a sealed union.
4. Remove the TODO comment once the type is declared.

## Related Rules

- [`RequireReturnTypeRector`](RequireReturnTypeRector.md) — enforces the same principle for return types
- [`RequireTypedPropertyRector`](RequireTypedPropertyRector.md) — enforces typed class properties
