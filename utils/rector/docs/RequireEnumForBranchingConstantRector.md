# RequireEnumForBranchingConstantRector

Detects `if/elseif` chains or `switch` statements that compare a single variable against 3 or more distinct string or integer literals — flagging the implicit closed set as an enum candidate.

**Category:** Enum Design
**Mode:** `warn` only (no auto-fix)
**Auto-fix:** No

## Rationale

When a variable is compared against a fixed set of literals across a branch chain, the set is implicitly closed but untracked. Adding a new value requires manually finding every branch in the codebase. Extracting to a backed enum makes the set explicit and exhaustive-checked by tooling.

## What It Detects

`If_` nodes with elseif branches and `Switch_` nodes where:
- A single variable is compared against 3+ distinct string or integer literals
- The comparisons use `===` or `==` (equality), not range operators (`>`, `<`, `>=`, `<=`)
- Each branch compares the **same** variable

The rule does not fire on:
- Fewer than `minCases` distinct literals
- Branches that compare different variables
- Switch/if where the subject is not a simple variable

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | Only `'warn'` is meaningful; `'auto'` is a no-op |
| `minCases` | `int` | `3` | Minimum number of distinct literals to trigger the warning |
| `message` | `string` | see below | TODO comment template with `%s` (varName), `%d` (count), `%s` (literals) |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(RequireEnumForBranchingConstantRector::class, [
    'mode' => 'warn',
    'minCases' => 3,
    'message' => 'TODO: [RequireEnumForBranchingConstantRector] $%s is compared against %d literals (%s) — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md',
]);
```

## Examples

### if/elseif chain — before
```php
function handle(string $status): string
{
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    } elseif ($status === 'pending') {
        return 'Pending';
    }
    return 'Unknown';
}
```

### if/elseif chain — after
```php
function handle(string $status): string
{
    // TODO: [RequireEnumForBranchingConstantRector] $status is compared against 3 literals ('active', 'inactive', 'pending') — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    } elseif ($status === 'pending') {
        return 'Pending';
    }
    return 'Unknown';
}
```

### switch on string — before
```php
function describe(string $color): string
{
    switch ($color) {
        case 'red':
            return 'Red';
        case 'green':
            return 'Green';
        case 'blue':
            return 'Blue';
        default:
            return 'Unknown';
    }
}
```

### switch on string — after
```php
function describe(string $color): string
{
    // TODO: [RequireEnumForBranchingConstantRector] $color is compared against 3 literals ('red', 'green', 'blue') — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    switch ($color) {
        case 'red':
            return 'Red';
        case 'green':
            return 'Green';
        case 'blue':
            return 'Blue';
        default:
            return 'Unknown';
    }
}
```

### switch on int — before
```php
function label(int $code): string
{
    switch ($code) {
        case 1:
            return 'One';
        case 2:
            return 'Two';
        case 3:
            return 'Three';
        default:
            return 'Other';
    }
}
```

### switch on int — after
```php
function label(int $code): string
{
    // TODO: [RequireEnumForBranchingConstantRector] $code is compared against 3 literals (1, 2, 3) — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    switch ($code) {
        case 1:
            return 'One';
        case 2:
            return 'Two';
        case 3:
            return 'Three';
        default:
            return 'Other';
    }
}
```

## Resolution

When you see the TODO comment:

1. Declare a backed enum with one case per literal value.
2. Replace the `if/elseif` chain or `switch` with a `match()` expression on the enum.
3. Update call sites to pass enum cases instead of raw literals (use `ForbidStringArgForEnumParamRector` to enforce this).
4. Add `#[ClosedSet]` to the enum to document that the set is intentionally closed.

## Caveats

- The rule only tracks comparisons using `===` or `==`. Range comparisons (`>`, `>=`, `<`, `<=`) are intentionally ignored.
- The rule fires on the `If_` node of the chain. `elseif` branches are not processed independently; the entire chain is evaluated from the root `if`.
- The comment is idempotent — re-running will not duplicate the TODO if it is already present.

## Related Rules

- [`RequireExhaustiveMatchOnEnumRector`](RequireExhaustiveMatchOnEnumRector.md) — once you have a backed enum, enforces that `match()` covers all cases
- [`ForbidStringArgForEnumParamRector`](ForbidStringArgForEnumParamRector.md) — enforces enum cases at call sites after extraction
- [`SuggestEnumForStringPropertyRector`](SuggestEnumForStringPropertyRector.md) — detects the same implicit set via property assignments rather than branch comparisons
