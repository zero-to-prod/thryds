# MakeClassReadonlyRector

Adds the `readonly` modifier to classes that have no mutable state.

**Category:** Code Quality
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

PHP `readonly` classes guarantee immutability at the language level and allow OPcache to optimise property access. When all properties are already effectively immutable (all promoted constructor parameters, or no properties at all), the class should carry the `readonly` modifier explicitly. Without it, the guarantee is implicit and redundant `readonly` keywords are scattered across every property.

## What It Detects

Classes without the `readonly` modifier whose properties are either all promoted constructor params (with or without `readonly` on each param) or where there are no non-static, typed, mutable properties and no property writes outside the constructor.

```php
class UserDto { public function __construct(public readonly string $name) {} }
```

## Transformation

### In `auto` mode

Adds `readonly` to the class declaration and strips redundant `readonly` from individual property declarations and promoted constructor parameters.

### In `warn` mode

Adds a `// <message>` comment above the class declaration.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(MakeClassReadonlyRector::class, [
    'mode' => 'auto',
]);
```

## Example

### Before
```php
class UserDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
```

### After
```php
readonly class UserDto
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}
}
```

### Before (no properties)
```php
class EmptyService
{
    public function handle(): void
    {
        echo 'handled';
    }
}
```

### After
```php
readonly class EmptyService
{
    public function handle(): void
    {
        echo 'handled';
    }
}
```

## Related Rules

- [`LimitConstructorParamsRector`](LimitConstructorParamsRector.md) — classes reduced to a DTO shape become candidates for `readonly`
- [`ForbidArrayShapeReturnRector`](ForbidArrayShapeReturnRector.md) — generated Result DTOs are emitted as `readonly` classes
