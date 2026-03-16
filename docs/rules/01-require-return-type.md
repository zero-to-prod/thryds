# RequireReturnTypeRector

## Tool

Rector (custom rule)

## What it does

Automatically adds return type declarations to functions and methods by inferring the
type from the AST. When the type cannot be determined, adds a TODO comment instead.

## Why it matters

Return types let an agent trace data flow across files without reading function bodies.
When every function declares what it returns, an agent can resolve types through call
chains by reading signatures alone — no execution, no guessing.

## Inference strategy

The rule walks all `return` statements in the function body and resolves each to a type:

| Return expression | Inferred type |
|---|---|
| `return new Foo()` | `Foo` |
| `return $this` | `static` |
| `return true` / `return false` | `bool` |
| `return 42` / `return 3.14` | `int` / `float` |
| `return 'string'` | `string` |
| `return []` or `return [...]` | `array` |
| `return null` | adds `null` to union |
| No return / empty `return;` | `void` (if no other returns exist) |
| `return $typedParam` | Use `$param`'s declared type |
| `return $this->typedProperty` | Use property's declared type |
| `return $var` (from typed call) | Use return type of the assigned call |
| Anything else | Cannot infer — fall back to TODO |

When multiple return statements exist, the types are merged:
- All same type → that type
- Mix of `Foo` and `null` → `?Foo`
- Mix of incompatible types → union type (e.g., `string|int`)
- Any unresolvable return in the set → abort, fall back to TODO

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `skipMagicMethods` | `bool` | `true` | Skip `__construct`, `__destruct`, and other magic methods |
| `skipClosures` | `bool` | `false` | Skip closures and arrow functions |
| `todoMessage` | `string` | `'TODO: Add return type'` | Comment text when inference fails |

### Example rector.php

```php
use Utils\Rector\Rector\RequireReturnTypeRector;

$rectorConfig->ruleWithConfiguration(RequireReturnTypeRector::class, [
    'skipMagicMethods' => true,
    'skipClosures' => false,
]);
```

## Before / After

### Auto-fix: literal returns

```php
// before
function isActive(User $User) {
    return $User->status === Status::active;
}

// after
function isActive(User $User): bool {
    return $User->status === Status::active;
}
```

### Auto-fix: constructor return

```php
// before
function createUser(string $name) {
    return new User($name);
}

// after
function createUser(string $name): User {
    return new User($name);
}
```

### Auto-fix: nullable

```php
// before
function find(int $id) {
    if ($id <= 0) {
        return null;
    }
    return new User($id);
}

// after
function find(int $id): ?User {
    if ($id <= 0) {
        return null;
    }
    return new User($id);
}
```

### Auto-fix: void

```php
// before
function save(User $User) {
    $this->repository->persist($User);
}

// after
function save(User $User): void {
    $this->repository->persist($User);
}
```

### Fallback: unresolvable

```php
// before
function transform($data) {
    return $this->pipeline->run($data);
}

// after — pipeline return type unknown
// TODO: Add return type
function transform($data) {
    return $this->pipeline->run($data);
}
```

## Implementation notes

- **Node types**: `ClassMethod`, `Function_`, `Closure`, `ArrowFunction`
- **Type resolution**: Use `NodeTypeResolver::getType()` on each `Return_->expr` to get
  `PHPStan\Type\Type`. Convert to a PHP type node via `StaticTypeMapper::mapPHPStanTypeToPhpParserNode()`.
  If mapping returns `null`, the type is unresolvable.
- **Void detection**: If the function has zero `return` statements (or only bare `return;`),
  and is not abstract, set return type to `void`.
- **Arrow functions**: The body is `$node->expr` — resolve its type directly.
- **Union types**: When returns yield different types, build a `UnionType` node. If PHP
  version < 8.0 is targeted, fall back to TODO for unions.
- **Skip logic**: Check for existing return type (`$node->returnType !== null`). Check for
  existing TODO in leading comments. Skip magic methods when configured.
- Implements `ConfigurableRectorInterface`
