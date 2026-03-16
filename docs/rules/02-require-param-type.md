# RequireParamTypeRector

## Tool

Rector (custom rule)

## What it does

Automatically adds type declarations to untyped parameters by inferring the type from
default values, docblocks, and usage patterns in the function body. When the type cannot
be determined, adds a TODO comment instead.

## Why it matters

Untyped parameters force an agent to read every call site to infer what a function
accepts. Typed parameters make each function a self-contained contract. Combined with
return types, they let an agent reason about any function in isolation.

## Inference strategy (applied in order, first match wins)

| Source | How it infers | Example |
|---|---|---|
| Default value | Type of the literal | `$limit = 10` → `int` |
| Default `null` + usage | Nullable + inferred | `$name = null` then `strlen($name)` → `?string` |
| `@param` docblock | Parse PHPDoc type | `@param string $name` → `string` |
| Type-hinted call argument | Match param to callee signature | `strlen($name)` expects `string` → `string` |
| Comparison to typed value | Infer from operator context | `$id === (int) $x` → `int` |
| Property assignment | Use property's declared type | `$this->name = $name` where `string $name` → `string` |

### When inference fails

If none of the above strategies produce a type, add a TODO comment to the function:
```php
// TODO: Add param type for $data
```

### Safety rules

- Never infer `mixed` — that provides no value. Fall back to TODO instead.
- When a default value is `null` and a type is inferred from usage, always make it
  nullable (`?Type`).
- When a `@param` annotation contains a union type, map it directly if PHP 8.0+ unions
  are available. Otherwise fall back to TODO.

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `skipVariadic` | `bool` | `true` | Skip variadic parameters (`...$args`) |
| `skipClosures` | `bool` | `false` | Skip closures and arrow functions |
| `useDocblocks` | `bool` | `true` | Infer types from `@param` annotations |
| `todoMessage` | `string` | `'TODO: Add param type'` | Comment text when inference fails |

### Example rector.php

```php
use Utils\Rector\Rector\RequireParamTypeRector;

$rectorConfig->ruleWithConfiguration(RequireParamTypeRector::class, [
    'skipVariadic' => true,
    'useDocblocks' => true,
]);
```

## Before / After

### Auto-fix: default value

```php
// before
function paginate($page = 1, $perPage = 25) {
    return $this->query->skip(($page - 1) * $perPage)->take($perPage)->get();
}

// after
function paginate(int $page = 1, int $perPage = 25) {
    return $this->query->skip(($page - 1) * $perPage)->take($perPage)->get();
}
```

### Auto-fix: docblock

```php
// before
/** @param string $name */
function greet($name) {
    return "Hello, $name";
}

// after
function greet(string $name): string {
    return "Hello, $name";
}
```

### Auto-fix: nullable default

```php
// before
function find($id = null) {
    return $id ? $this->repo->find($id) : null;
}

// after — inferred int from repo->find() signature, nullable from default
function find(?int $id = null) {
    return $id ? $this->repo->find($id) : null;
}
```

### Auto-fix: property assignment

```php
// before
class Service {
    private string $name;

    public function setName($name): void {
        $this->name = $name;
    }
}

// after
class Service {
    private string $name;

    public function setName(string $name): void {
        $this->name = $name;
    }
}
```

### Fallback: unresolvable

```php
// before
function process($data) {
    return $this->pipeline->run($data);
}

// after
// TODO: Add param type for $data
function process($data) {
    return $this->pipeline->run($data);
}
```

## Implementation notes

- **Node types**: `ClassMethod`, `Function_`, `Closure`, `ArrowFunction`
- **Per-param processing**: Iterate `$node->params`. For each param where
  `$param->type === null`, run the inference chain.
- **Default value inference**: Check `$param->default`. Map `LNumber` → `int`,
  `DNumber` → `float`, `String_` → `string`, `ConstFetch(true/false)` → `bool`,
  `ConstFetch(null)` → flag as nullable.
- **Docblock inference**: Use `PhpDocInfoFactory` to parse the function's docblock.
  Look up `@param` tags matching the param name. Convert the PHPDoc type via
  `StaticTypeMapper`.
- **Usage inference**: Use `traverseNodesWithCallable` on the function body. Track
  where the param variable appears as an argument to a typed function/method. Use
  `ReflectionResolver` to get the callee's param type at that position.
- **Property assignment inference**: When the param is assigned to `$this->prop`,
  look up the property's declared type on the class.
- **Comment placement**: Attach TODO to the function node, not individual params.
  Include the param name in the message for actionability.
- Implements `ConfigurableRectorInterface`
