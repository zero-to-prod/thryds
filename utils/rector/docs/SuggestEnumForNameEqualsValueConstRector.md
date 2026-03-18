# SuggestEnumForNameEqualsValueConstRector

Flags `readonly` classes where every public string constant has a name identical to its value, suggesting migration to a backed enum.

**Category:** Enum Design
**Mode:** `warn` only (no auto-fix)
**Auto-fix:** No

## Rationale

When a constants class looks like:

```php
public const string production = 'production';
public const string staging = 'staging';
```

the name-equals-value pattern is the canonical signal that these are not arbitrary keys — they are a finite closed set of values. That is exactly what a backed enum models. A backed enum provides exhaustiveness checking, prevents invalid values from being constructed, and integrates with PHP's match expressions and type system. This rule identifies the pattern and suggests migration.

Classes already annotated with `#[ClosedSet]` or `#[KeyRegistry]` are excluded, as they have already been classified.

## What It Detects

`readonly` classes that are pure constants classes (no methods, no properties) where:
- All public `string`-typed constants have `const_name === const_value` (e.g., `case foo = 'foo'`).
- The count of such constants meets or exceeds `minConstants`.
- The class does not have any `excludedAttributes` (`#[ClosedSet]`, `#[KeyRegistry]`).

If any public string constant has a name that differs from its value, the class is disqualified (the pattern must be uniform).

## Transformation

### In `warn` mode

A TODO comment is prepended to the class declaration:

```
// TODO: [SuggestEnumForNameEqualsValueConstRector] BladeDirectives has 3 string constants where name equals value — consider migrating to a backed enum.
```

This rule has no `auto` mode.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `minConstants` | `int` | `2` | Minimum number of qualifying constants to trigger the rule |
| `excludedAttributes` | `string[]` | `[]` | Classes with these attributes are skipped |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective |
| `message` | `string` | see source | `sprintf` template; receives `(className, constCount)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(SuggestEnumForNameEqualsValueConstRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [SuggestEnumForNameEqualsValueConstRector] %s has %d string constants where name equals value — consider migrating to a backed enum.',
    'minConstants' => 2,
    'excludedAttributes' => [ClosedSet::class, KeyRegistry::class],
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
}
```

### After

```php
// TODO: [SuggestEnumForNameEqualsValueConstRector] BladeDirectives has 3 string constants where name equals value — consider migrating to a backed enum.
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';
}
```

## Resolution

When you see the TODO comment from this rule:
1. Convert the constants class to a backed string enum:
   ```php
   #[ClosedSet]
   enum BladeDirectives: string
   {
       case production = 'production';
       case env = 'env';
       case vite = 'vite';
   }
   ```
2. Update all call sites from `BladeDirectives::production` (string constant) to `BladeDirectives::production->value` (enum case backing value), or accept the enum type directly.
3. Add `#[ClosedSet]` to satisfy `RequireClosedSetOnBackedEnumRector`.
4. If migration is not yet possible, add `#[KeyRegistry]` or `#[ClosedSet]` to silence this rule until migration is planned.

## Related Rules

- [`RequireClosedSetOnBackedEnumRector`](RequireClosedSetOnBackedEnumRector.md) — requires `#[ClosedSet]` on every backed enum
- [`RequireNamesKeysOnConstantsClassRector`](RequireNamesKeysOnConstantsClassRector.md) — requires `#[NamesKeys]` if keeping it as a constants class
- [`SuggestEnumForInternalOnlyConstantsRector`](SuggestEnumForInternalOnlyConstantsRector.md) — suggests backed enum for internally-referenced constants in mixed classes
