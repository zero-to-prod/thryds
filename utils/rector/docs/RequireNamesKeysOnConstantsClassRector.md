# RequireNamesKeysOnConstantsClassRector

Requires a `#[NamesKeys]` (or `#[KeyRegistry]`) attribute on `readonly` classes whose body contains only string constants and no methods or properties.

**Category:** Constants Class Design
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

A `readonly` class with nothing but `public const string` members is a key registry — it names things (cache keys, array keys, log context fields, etc.). The project convention (ADR-007) requires annotating such classes with `#[NamesKeys]` to declare what domain those keys belong to. Without this annotation, there is no machine-readable signal that distinguishes a key registry from any other constants holder, making automated governance impossible.

The distinction matters: constants name things (use a class), enumerations define finite sets (use a backed enum). This rule enforces the former is properly declared.

## What It Detects

`readonly` classes that:
- Contain at least one `ClassConst` member.
- Contain no `ClassMethod` or `Property` members.
- Do not already have the configured `attributeClass` (`#[NamesKeys]` / `#[KeyRegistry]`).
- Do not have any of the configured `excludedAttributes` (e.g., `#[ViewModel]`).

## Transformation

### In `auto` mode

The rule prepends `#[NamesKeys(source: 'TODO: describe source')]` to the class. The `source` argument is a placeholder that must be filled in manually.

### In `warn` mode

A TODO comment is prepended to the class declaration:

```
// TODO: [RequireNamesKeysOnConstantsClassRector] CacheKey contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `attributeClass` | `string` | `''` | FQN of the attribute to require (e.g., `KeyRegistry::class`) |
| `excludedAttributes` | `string[]` | `[]` | Classes already annotated with these are skipped (e.g., `ViewModel::class`) |
| `mode` | `string` | `'warn'` | `'auto'` to add the attribute, `'warn'` to add a TODO comment |
| `message` | `string` | see source | `sprintf` template; receives `(className)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireNamesKeysOnConstantsClassRector::class, [
    'attributeClass' => KeyRegistry::class,
    'excludedAttributes' => [ViewModel::class],
    'mode' => 'warn',
    'message' => 'TODO: [RequireNamesKeysOnConstantsClassRector] %s contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).',
]);
```

## Example

### Before

```php
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}
```

### After

```php
// TODO: [RequireNamesKeysOnConstantsClassRector] CacheKey contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}
```

## Resolution

When you see the TODO comment from this rule:
1. Decide what domain or data structure these keys name (e.g., "Redis cache keys", "log context fields").
2. Add `#[KeyRegistry(source: 'description of what these keys name')]` to the class.
3. If all constant names equal their values and there are no other members, also consider whether a backed enum with `#[ClosedSet]` is more appropriate (see `SuggestEnumForNameEqualsValueConstRector`).

## Related Rules

- [`RequireNamesKeysOnMixedConstantsClassRector`](RequireNamesKeysOnMixedConstantsClassRector.md) — same requirement for classes that also contain methods
- [`SuggestEnumForNameEqualsValueConstRector`](SuggestEnumForNameEqualsValueConstRector.md) — suggests converting name-equals-value const classes to backed enums
- [`RequireClosedSetOnBackedEnumRector`](RequireClosedSetOnBackedEnumRector.md) — requires `#[ClosedSet]` on backed enums
