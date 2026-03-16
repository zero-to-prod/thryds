# ForbidArrayShapeReturnRector

## Tool

Rector (custom rule)

## What it does

Automatically replaces associative-array returns with a typed class. Generates a new
readonly DTO class from the array's keys and value types, rewrites the function to return
an instance of it, and updates the return type. When the array shape cannot be fully
determined, adds a TODO comment instead.

## Why it matters

An `array` return type tells an agent nothing about the shape of the data. A typed class
makes the shape explicit — the agent inspects the class definition once and knows the
contract everywhere it appears.

## Refactoring strategy

### Auto-fix: extract literal array to DTO

When a function has `array` return type and returns an associative array literal with
string keys, the rule generates a readonly class and rewrites the return.

```php
// before
class UserService {
    public function getProfile(int $id): array {
        $User = $this->repository->find($id);

        return [
            'name' => $User->name,
            'email' => $User->email,
            'role' => $User->role->value,
        ];
    }
}

// after — new file: UserServiceGetProfileResult.php
readonly class UserServiceGetProfileResult {
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
    ) {}
}

// modified:
class UserService {
    public function getProfile(int $id): UserServiceGetProfileResult {
        $User = $this->repository->find($id);

        return new UserServiceGetProfileResult(
            name: $User->name,
            email: $User->email,
            role: $User->role->value,
        );
    }
}
```

### Value type inference

For each array key, the rule infers the value's type:

| Value expression | Inferred property type |
|---|---|
| `$TypedVar->property` | Property's declared type |
| `$TypedVar->method()` | Method's return type |
| `'string literal'` | `string` |
| `42` / `3.14` | `int` / `float` |
| `true` / `false` | `bool` |
| `new Foo()` | `Foo` |
| Unresolvable | `mixed` (flags for TODO fallback) |

### When extraction is not safe (fall back to TODO)

- The function has multiple return statements with different array shapes (different
  keys across returns).
- The returned array is built dynamically (keys from variables, spread operator).
- Any value type resolves to `mixed` and `allowMixed` is false.
- The function does not return an array literal (returns a variable).

```php
// TODO: Replace array return with a typed class
function buildResponse(bool $detailed): array {
    $base = ['status' => 'ok'];
    if ($detailed) {
        $base['debug'] = getDebugInfo(); // dynamic shape
    }
    return $base;
}
```

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `minKeys` | `int` | `2` | Minimum number of string keys in a returned array to trigger |
| `skipPrivateMethods` | `bool` | `false` | Skip private methods |
| `classSuffix` | `string` | `'Result'` | Suffix for generated class name |
| `outputDir` | `string` | `''` | Directory for generated files. Empty = same directory as source. |
| `dataModelTrait` | `string` | `''` | Fully qualified trait to `use` in generated class instead of promoted constructor params |
| `allowMixed` | `bool` | `false` | When true, allow `mixed` property types. When false, abort to TODO. |
| `todoMessage` | `string` | `'TODO: Replace array return with a typed class'` | Comment when auto-fix is not safe |

### Example rector.php

```php
use Utils\Rector\Rector\ForbidArrayShapeReturnRector;

$rectorConfig->ruleWithConfiguration(ForbidArrayShapeReturnRector::class, [
    'minKeys' => 2,
    'classSuffix' => 'Result',
    'outputDir' => __DIR__ . '/src/DTOs',
]);
```

## Implementation notes

- **Node types**: `ClassMethod`, `Function_`
- **Detection**:
  1. Check `$node->returnType` resolves to `array`.
  2. Collect all `Return_` nodes. Verify each returns an `Array_` literal.
  3. For each `Array_`, collect items where `$item->key` is `String_`.
  4. Verify all return statements have the same set of keys.
- **Type resolution**: For each value expression, use `NodeTypeResolver::getType()` to
  get `PHPStan\Type\Type`, then `StaticTypeMapper::mapPHPStanTypeToPhpParserNode()` to
  produce a PHP type node. If mapping returns null and `allowMixed` is false, abort.
- **Class generation**: Build a `Class_` node with the `readonly` flag. Create a
  constructor with promoted `public` params — one per array key, with inferred types.
- **Class naming**: `{ClassName}{MethodName}{classSuffix}` for methods,
  `{FunctionName}{classSuffix}` for functions. Ensure uniqueness.
- **Return rewriting**: Replace `return [...]` with `return new ClassName(key: value, ...)`.
  Use named arguments mapped from the original array keys.
- **Return type rewriting**: Replace `array` with the new class name.
- **File output**: Write the new class file to `outputDir`. Add `use` import to the
  source file.
- Implements `ConfigurableRectorInterface`
