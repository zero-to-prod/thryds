# ForbidMixedTypeRector

## Tool

Rector (custom rule)

## What it does

Automatically replaces explicit `mixed` type declarations with specific types inferred
from docblocks, usage patterns, and assignment context. When the actual type cannot be
determined, adds a TODO comment instead.

## Why it matters

`mixed` is technically a type declaration, but it communicates nothing. It tells an agent
"this could be anything" — which is the same as having no type at all, except it looks
like the developer made a conscious choice.

`mixed` also masks incomplete typing during migrations. It satisfies static analysis
thresholds without providing real type safety.

## Inference strategy (applied per-node, first match wins)

### For parameters (`mixed $param`)

| Source | Inference |
|---|---|
| `@param` docblock | Use PHPDoc type directly |
| Assigned to typed property | `$this->name = $param` where `string $name` → `string` |
| Passed to typed function | `strlen($param)` expects `string` → `string` |
| Compared to typed value | `$param === 0` → `int` |
| Default value present | `mixed $x = []` → `array` |

### For return types (`: mixed`)

| Source | Inference |
|---|---|
| `@return` docblock | Use PHPDoc type directly |
| Return expression types | Same logic as RequireReturnTypeRector inference |

### For properties (`mixed $prop`)

| Source | Inference |
|---|---|
| `@var` docblock | Use PHPDoc type directly |
| Constructor assignment | `$this->prop = $typedParam` → use param's type |
| All assignment sites agree | If every `$this->prop = expr` resolves to the same type → use it |

```php
// before
class EventDispatcher {
    /** @var callable[] */
    private mixed $listeners;

    /** @param array<string, mixed> $config */
    public function __construct(mixed $config) {
        $this->listeners = [];
    }

    public function dispatch(string $event, mixed $payload): mixed {
        return $this->listeners[$event]($payload);
    }
}

// after
class EventDispatcher {
    private array $listeners;

    public function __construct(array $config) {
        $this->listeners = [];
    }

    // TODO: Replace mixed type with a specific type
    public function dispatch(string $event, mixed $payload): mixed {
        return $this->listeners[$event]($payload);
    }
}
```

### When inference fails (fall back to TODO)

- No docblock, no typed usage, no assignment context.
- Conflicting types across usage sites (e.g., used as both `string` and `int`).
- Inferred type would be `mixed` again (circular — skip).

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `checkParams` | `bool` | `true` | Process `mixed` parameter types |
| `checkReturnTypes` | `bool` | `true` | Process `mixed` return types |
| `checkProperties` | `bool` | `true` | Process `mixed` property types |
| `useDocblocks` | `bool` | `true` | Infer types from PHPDoc annotations |
| `todoMessage` | `string` | `'TODO: Replace mixed type with a specific type'` | Comment when inference fails |

### Example rector.php

```php
use Utils\Rector\Rector\ForbidMixedTypeRector;

$rectorConfig->ruleWithConfiguration(ForbidMixedTypeRector::class, [
    'checkParams' => true,
    'checkReturnTypes' => true,
    'checkProperties' => true,
    'useDocblocks' => true,
]);
```

## Implementation notes

- **Node types**: `ClassMethod`, `Function_`, `Closure`, `ArrowFunction`, `Property`
- **Mixed detection**: Check if the type node is an `Identifier` with name `mixed`.
- **Docblock inference**: Use `PhpDocInfoFactory` to parse the node's docblock.
  For params, look up `@param` by name. For return types, look up `@return`. For
  properties, look up `@var`. Convert via `StaticTypeMapper`.
- **Usage inference**: Same approach as RequireParamTypeRector — traverse the function
  body, find typed contexts where the variable is used, resolve the expected type.
- **Property inference**: Scan all methods in the class for assignments to the property.
  Collect the RHS types. If all agree, use that type.
- **Type replacement**: Replace the `Identifier('mixed')` node with the inferred PHP
  type node. For union types, build a `UnionType` node.
- **Reporting**: Attach TODO comment to the function/property node. For functions with
  multiple `mixed` params, report once per function, listing all unresolvable param
  names.
- Implements `ConfigurableRectorInterface`
