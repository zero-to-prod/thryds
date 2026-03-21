# ForbidReflectionInInstanceMethodRector

Flags Reflection API instantiation inside non-constructor instance methods, where it runs per-invocation instead of once at construction.

**Category:** Performance / AOP
**Mode:** `warn`
**Auto-fix:** No

## Rationale

In FrankenPHP worker mode, instance methods on long-lived objects execute per-request. Reflection on static class structure (attributes, methods, properties) produces the same result every time — instantiating it per-invocation wastes cycles. Resolving reflection at construction ensures it runs once during boot.

## What It Detects

`new ReflectionClass`, `new ReflectionMethod`, `new ReflectionProperty`, `new ReflectionFunction`, or `new ReflectionEnum` inside a non-constructor, non-static instance method.

### In `warn` mode

```
// TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |

## Example

### Before

```php
class Validator {
    public function validate(object $model): array {
        $ref = new ReflectionClass($model);
    }
}
```

### After

```php
class Validator {
    public function validate(object $model): array {
        // TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
        $ref = new ReflectionClass($model);
    }
}
```

## Resolution

When you see the TODO comment from this rule:

1. Move the `new Reflection*` call into the constructor and store the result as a property
2. If the reflection target varies by argument, cache results in a `static` array keyed by class name
3. Use the cached property/result in the instance method instead

## Allowed

- `__construct` — reflection at construction runs once per object lifetime
- `static` methods — typically called at boot, not per-request

## Related Rules

None yet.
