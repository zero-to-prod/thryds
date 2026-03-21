# ForbidReflectionInClosureRector

Flags Reflection API instantiation inside closures, where it executes per-invocation rather than once at boot.

**Category:** Performance / AOP
**Mode:** `warn`
**Auto-fix:** No

## Rationale

In FrankenPHP worker mode, closures registered as request handlers execute per-request. Reflection on static class structure (attributes, properties, methods) yields the same result every time — instantiating it inside a closure wastes cycles on every request. Hoisting reflection to the enclosing boot scope resolves it once and captures the result in the closure's `use` list.

## What It Detects

`new ReflectionClass`, `new ReflectionMethod`, `new ReflectionProperty`, `new ReflectionFunction`, or `new ReflectionEnum` inside any closure body.

## Transformation

### In `auto` mode

No auto-fix — the hoist target varies by context.

### In `warn` mode

```
// TODO: Reflection in closures runs per-invocation; hoist to the enclosing boot scope. See: utils/rector/docs/ForbidReflectionInClosureRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |

## Example

### Before

```php
$handler = function (object $model): array {
    $ref = new ReflectionClass($model);
    return $ref->getProperties();
};
```

### After

```php
$handler = function (object $model): array {
    // TODO: Reflection in closures runs per-invocation; hoist to the enclosing boot scope. See: utils/rector/docs/ForbidReflectionInClosureRector.md
    $ref = new ReflectionClass($model);
    return $ref->getProperties();
};
```

## Resolution

When you see the TODO comment from this rule:

1. Move the `new Reflection*()` call to the scope that defines the closure (boot, constructor, or top-level script)
2. Pass the reflection result into the closure via `use ($reflectionResult)`
3. If the reflection target is dynamic (varies per-request), consider a cache keyed by class name

## Related Rules

- `ForbidReflectionInInstanceMethodRector` — same principle applied to class instance methods
