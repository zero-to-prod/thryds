# SuggestEnumForInternalOnlyConstantsRector

Flags `readonly` classes that contain both methods and string constants where every constant is only referenced internally via `self::`, suggesting migration to a backed enum.

**Category:** Enum Design
**Mode:** `warn` only (no auto-fix)
**Auto-fix:** No

## Rationale

When a class's public string constants are only ever used within that same class (via `self::constName`), they are an implementation detail, not a public API. That pattern indicates the constants form a private enumeration — a finite set of values the class uses internally. A backed enum with `#[ClosedSet]` is a more precise model: it provides exhaustiveness checking, prevents invalid values, and makes the set explicit and type-safe.

## What It Detects

`readonly` classes that:
- Contain at least one `ClassMethod`.
- Have at least `minConstants` public `string`-typed constants.
- Every one of those constants is referenced via `self::constName` somewhere within the class body.
- No constant name appears in a reference outside the class (i.e., all references found are `self::` references).
- Do not have any `excludedAttributes`.

If any public string constant is not referenced via `self::`, the class does not qualify (the constants may be part of a public key registry contract).

## Transformation

### In `warn` mode

A TODO comment is prepended to the class declaration:

```
// TODO: [SuggestEnumForInternalOnlyConstantsRector] BladeDirectives has 3 string constants only referenced internally — consider migrating to a backed enum.
```

This rule has no `auto` mode.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `minConstants` | `int` | `2` | Minimum number of qualifying constants to trigger |
| `excludedAttributes` | `string[]` | `[]` | Classes with these attributes are skipped |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective |
| `message` | `string` | see source | `sprintf` template; receives `(className, constCount)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(SuggestEnumForInternalOnlyConstantsRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [SuggestEnumForInternalOnlyConstantsRector] %s has %d string constants only referenced internally — consider migrating to a backed enum.',
]);
```

## Example

### Before

```php
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';

    public static function register(): void
    {
        echo self::production;
        echo self::env;
        echo self::vite;
    }
}
```

### After

```php
// TODO: [SuggestEnumForInternalOnlyConstantsRector] BladeDirectives has 3 string constants only referenced internally — consider migrating to a backed enum.
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';

    public static function register(): void
    {
        echo self::production;
        echo self::env;
        echo self::vite;
    }
}
```

## Resolution

When you see the TODO comment from this rule:
1. Replace the constants with a backed enum:
   ```php
   #[ClosedSet]
   enum BladeDirective: string
   {
       case production = 'production';
       case env = 'env';
       case vite = 'vite';
   }
   ```
2. Update the `self::constName` references within the class to `BladeDirective::caseName->value`.
3. Add `#[ClosedSet]` to satisfy `RequireClosedSetOnBackedEnumRector`.
4. If migration is premature, add an `excludedAttributes` entry to silence the rule for this class.

## Related Rules

- [`RequireClosedSetOnBackedEnumRector`](RequireClosedSetOnBackedEnumRector.md) — requires `#[ClosedSet]` on any backed enum produced by migration
- [`SuggestEnumForKeyRegistryWithMethodsRector`](SuggestEnumForKeyRegistryWithMethodsRector.md) — flags `#[KeyRegistry]` classes that mix constants and methods
- [`SuggestEnumForNameEqualsValueConstRector`](SuggestEnumForNameEqualsValueConstRector.md) — suggests enum migration for name-equals-value patterns in pure constants classes
