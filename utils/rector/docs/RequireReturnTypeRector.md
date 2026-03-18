# RequireReturnTypeRector

Add return type declarations to functions and methods by inferring from return statements, or add a TODO comment when inference fails.

**Category:** Type Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (when type is inferrable); adds TODO comment when it is not

## Rationale

Explicit return types are essential for both PHPStan analysis accuracy and OPcache optimization. OPcache can use typed function signatures to optimize object shape inference and JIT compilation hints. PHPStan needs return types to propagate type information across call boundaries. Without them, every consumer of an untyped function must work with `mixed`, which silences static analysis. This rule closes that gap automatically where possible, and surfaces the remaining cases as TODO comments.

Magic methods are skipped by default since PHP enforces their signatures separately.

## What It Detects

Any function, method, closure, or arrow function that has no return type declaration (`$node->returnType === null`), excluding abstract methods and magic methods (when `skipMagicMethods` is `true`).

## Transformation

### In `auto` mode
- **No `return` statements**: adds `: void`.
- **All bare `return;` statements**: adds `: void`.
- **Return statements with typed expressions**: infers the union type and maps it to a PHP type node. Adds `: void` as part of a nullable or union when bare returns are mixed in.
- **Arrow functions**: infers from the expression type.
- **Unresolvable / mixed types**: adds a `// TODO: Add return type` comment (regardless of `mode`, since `auto` cannot produce a type).

### In `warn` mode
Adds the configured `message` as a `//` comment above the function/method declaration for every untyped function (idempotent); still auto-adds the type when it can be inferred.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `skipMagicMethods` | `bool` | `true` | Skip `__construct`, `__toString`, etc. |
| `skipClosures` | `bool` | `false` | Skip `Closure` and arrow function nodes |
| `mode` | `string` | `'warn'` | `'auto'` to always infer and patch; `'warn'` to add TODO when type cannot be inferred |
| `message` | `string` | `'TODO: Add return type'` | Comment text for unresolvable cases |

Project config (`rector.php`): `skipMagicMethods => true`, `skipClosures => false`, `mode => 'auto'`.

## Example

### Before (no return)
```php
public function save(object $entity)
{
    $this->repository->persist($entity);
}
```

### After
```php
public function save(object $entity): void
{
    $this->repository->persist($entity);
}
```

### Before (inferred from comparison)
```php
public function isActive(): // no return type
{
    return $this->status === 'active';
}
```

### After
```php
public function isActive(): bool
{
    return $this->status === 'active';
}
```

### Before (inferred from `new`)
```php
function createUser(string $name)
{
    return new User($name);
}
```

### After
```php
function createUser(string $name): User
{
    return new User($name);
}
```

## Resolution

When you see `// TODO: Add return type` from this rule:
1. Inspect what the function returns — the type may be genuinely mixed or depend on dynamic data.
2. Add the explicit return type declaration: `: string`, `: ResponseInterface`, `: never`, etc.
3. If the function truly can return multiple unrelated types, consider refactoring to return a single type or a sealed union.
4. Remove the TODO comment once the type is declared.

## Related Rules

- [`RequireParamTypeRector`](RequireParamTypeRector.md) — enforces the same principle for parameter types
- [`RequireTypedPropertyRector`](RequireTypedPropertyRector.md) — enforces typed class properties for OPcache optimization
