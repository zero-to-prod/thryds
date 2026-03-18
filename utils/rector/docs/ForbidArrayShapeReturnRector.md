# ForbidArrayShapeReturnRector

Replaces methods and functions that return a typed associative array with a generated `readonly` DTO class.

**Category:** Code Quality
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (when the shape is consistent and all types are resolvable) — falls back to `warn` otherwise

## Rationale

Returning `array` from a function discards type information and forces callers to access values by string keys. A `readonly` DTO with typed constructor parameters makes the shape explicit, autocomplete-friendly, and refactor-safe. Once the shape is captured in a class, callers can use `->name` instead of `['name']`, and PHPStan can verify the access.

## What It Detects

`ClassMethod` and `Function_` nodes with `array` as their return type. The rule inspects all `return` statements: if they all return array literals with consistent string keys and resolvable value types, it proceeds with extraction. If any return is not an array literal, the keys vary between returns, types are mixed (and `allowMixed` is false), or dynamic array spreading is used, it adds a TODO comment instead.

## Transformation

### In `auto` mode

1. Derives a DTO class name: `<OwnerClass><MethodName>Result` (or `<FunctionName>Result` for functions).
2. Infers property types from the first return's values using PHPStan.
3. Generates a `readonly class <Name>Result` file in `outputDir`.
4. Rewrites every `return ['key' => $val]` to `return new <Name>Result(key: $val)`.
5. Updates the method/function's return type to `<Name>Result`.

### In `warn` mode

Adds `// TODO: Replace array return with a typed class` above the method or function.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `minKeys` | `int` | `2` | Minimum number of array keys required to trigger extraction |
| `classSuffix` | `string` | `'Result'` | Suffix appended to build the generated class name |
| `outputDir` | `string` | `''` | Directory for the generated class file; no file is written if empty or nonexistent |
| `skipPrivateMethods` | `bool` | `false` | Skip private methods |
| `dataModelTrait` | `string` | `''` | If set, generates a DataModel-style class instead of a readonly constructor class |
| `allowMixed` | `bool` | `false` | Allow `mixed`-typed properties in the generated DTO |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `'TODO: Replace array return with a typed class'` | Comment text |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(ForbidArrayShapeReturnRector::class, [
    'minKeys' => 2,
    'classSuffix' => 'Result',
    'mode' => 'auto',
]);
```

## Example

### Before
```php
class UserService {
    public function getProfile(): array {
        return [
            'name' => 'John',
            'email' => 'john@example.com',
        ];
    }
}
```

### After
```php
class UserService {
    public function getProfile(): UserServiceGetProfileResult {
        return new UserServiceGetProfileResult(name: 'John', email: 'john@example.com');
    }
}
```

A `UserServiceGetProfileResult.php` file is created in `outputDir`.

### Before (dynamic shape — warn fallback)
```php
class DynamicService {
    public function build(bool $detailed): array {
        $base = ['status' => 'ok'];
        if ($detailed) {
            $base['debug'] = 'info';
        }
        return $base;
    }
}
```

### After
```php
class DynamicService {
    // TODO: Replace array return with a typed class
    public function build(bool $detailed): array {
        $base = ['status' => 'ok'];
        if ($detailed) {
            $base['debug'] = 'info';
        }
        return $base;
    }
}
```

## Resolution

When you see the TODO comment:
1. Determine why extraction was blocked: inconsistent keys across `return` statements, non-literal returns (e.g. the array is built incrementally), or unresolvable value types.
2. Refactor the method so all `return` statements produce the same static array literal shape, or create the DTO class manually and update the return type.
3. If the array is built dynamically, create the DTO with a static factory method instead.

## Related Rules

- [`MakeClassReadonlyRector`](MakeClassReadonlyRector.md) — generated Result classes are `readonly` by construction
