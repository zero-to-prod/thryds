# SuggestEnumForKeyRegistryWithMethodsRector

Flags `#[KeyRegistry]` classes that also contain methods, suggesting that the constants be extracted to a backed enum with `#[ClosedSet]`.

**Category:** Enum Design
**Mode:** `warn` only (no auto-fix)
**Auto-fix:** No

## Rationale

A class annotated with `#[KeyRegistry]` declares that its string constants name keys in some domain. When that class also contains methods, it is playing two roles simultaneously: a key registry and a service/utility class. This is a design smell. The constants (the closed set of valid key names) belong in a backed enum with `#[ClosedSet]`, while the methods can remain in a separate class. The enum provides exhaustiveness checking and type safety that a constants class cannot.

## What It Detects

Classes that:
- Have the configured `attributeClass` (`#[KeyRegistry]`) applied.
- Contain at least one `ClassConst` member.
- Contain at least one `ClassMethod` member.

## Transformation

### In `warn` mode

A TODO comment is prepended to the class declaration:

```
// TODO: [SuggestEnumForKeyRegistryWithMethodsRector] BladeDirectives has #[KeyRegistry] but also contains methods — extract constants to a backed enum with #[ClosedSet].
```

This rule has no `auto` mode.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `attributeClass` | `string` | `''` | FQN of the `#[KeyRegistry]` attribute to detect |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective |
| `message` | `string` | see source | `sprintf` template; receives `(className)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(SuggestEnumForKeyRegistryWithMethodsRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [SuggestEnumForKeyRegistryWithMethodsRector] %s has #[KeyRegistry] but also contains methods — extract constants to a backed enum with #[ClosedSet].',
]);
```

## Example

### Before

```php
#[TestKeyRegistry(source: 'blade directives')]
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string vite = 'vite';

    public static function register(): void {}
}
```

### After

```php
// TODO: [SuggestEnumForKeyRegistryWithMethodsRector] BladeDirectives has #[KeyRegistry] but also contains methods — extract constants to a backed enum with #[ClosedSet].
#[TestKeyRegistry(source: 'blade directives')]
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string vite = 'vite';

    public static function register(): void {}
}
```

## Resolution

When you see the TODO comment from this rule:
1. Create a new backed enum for the constants:
   ```php
   #[ClosedSet(addCase: '...checklist...')]
   enum BladeDirective: string
   {
       case production = 'production';
       case vite = 'vite';
   }
   ```
2. Remove the constants from the original class and update its `#[KeyRegistry]` to reference the enum.
3. Update all `BladeDirectives::production` references to `BladeDirective::production->value`.
4. The methods can remain in the original class or a renamed service class.

## Related Rules

- [`RequireClosedSetOnBackedEnumRector`](RequireClosedSetOnBackedEnumRector.md) — requires `#[ClosedSet]` on the resulting backed enum
- [`SuggestEnumForNameEqualsValueConstRector`](SuggestEnumForNameEqualsValueConstRector.md) — suggests enum migration for name-equals-value constants classes
- [`SuggestEnumForInternalOnlyConstantsRector`](SuggestEnumForInternalOnlyConstantsRector.md) — flags internally-referenced constants that could be an enum
