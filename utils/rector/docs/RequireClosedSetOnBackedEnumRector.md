# RequireClosedSetOnBackedEnumRector

Requires a `#[ClosedSet]` attribute on every backed enum (string or int backed), signaling that the enum exhaustively defines its set of valid values.

**Category:** Enum Design
**Mode:** `warn` only (configurable, but `auto` is not implemented)
**Auto-fix:** No

## Rationale

The project principle "enumerations define sets" requires that every backed enum be explicitly declared as a closed set via `#[ClosedSet]`. This attribute is the machine-readable signal that:
- The enum is exhaustive — no values outside its cases are valid.
- Other rules (e.g., `ValidateChecklistPathsRector`, `SuggestEnumForNameEqualsValueConstRector`) can use it as an exclusion marker.
- Reviewers and tooling can identify which enums are intentionally finite.

Pure (non-backed) enums are skipped because they have no backing value.

## What It Detects

Any `enum` declaration that:
- Has a scalar type (`string` or `int` backing).
- Does not already have the configured `attributeClass` (`#[ClosedSet]`) applied.

## Transformation

### In `warn` mode

A TODO comment is prepended to the enum declaration:

```
// TODO: [RequireClosedSetOnBackedEnumRector] Backed enum Permission must declare #[ClosedSet] — enums define sets (ADR-007).
```

There is no `auto` mode that adds the attribute automatically, because `#[ClosedSet]` may carry constructor arguments (e.g., `addCase`) that require human input.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `attributeClass` | `string` | `''` | FQN of the `#[ClosedSet]` attribute class to require |
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is currently a no-op |
| `message` | `string` | see source | `sprintf` template; receives `(enumName)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireClosedSetOnBackedEnumRector::class, [
    'attributeClass' => ClosedSet::class,
    'mode' => 'warn',
]);
```

## Example

### Before

```php
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}
```

### After

```php
// TODO: [RequireClosedSetOnBackedEnumRector] Backed enum Permission must declare #[ClosedSet] — enums define sets (ADR-007).
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}
```

## Resolution

When you see the TODO comment from this rule:
1. Add `#[ClosedSet]` to the enum. If this enum has an `addCase` checklist, provide it: `#[ClosedSet(addCase: '1. Add case here. 2. Update scripts/foo.php.')]`.
2. List every file or step that must be updated when a new case is added — this becomes the `addCase` checklist that `ValidateChecklistPathsRector` will validate.
3. Run `./run check:all` to confirm the attribute is recognized.

## Related Rules

- [`SuggestEnumForNameEqualsValueConstRector`](SuggestEnumForNameEqualsValueConstRector.md) — suggests creating a backed enum from a constants class where all names equal values
- [`ValidateChecklistPathsRector`](ValidateChecklistPathsRector.md) — validates file paths referenced in `#[ClosedSet(addCase: '...')]`
- [`RequireNamesKeysOnConstantsClassRector`](RequireNamesKeysOnConstantsClassRector.md) — requires `#[NamesKeys]` on pure constants classes (the non-enum alternative)
