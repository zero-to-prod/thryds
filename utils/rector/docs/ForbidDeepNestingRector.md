# ForbidDeepNestingRector

Reduces excessive nesting depth in methods and functions by inverting conditions into guard clauses, early returns, and early continues.

**Category:** Code Quality
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (partially — unreducible nesting falls back to `warn`)

## Rationale

Deep nesting forces readers to track multiple open conditions simultaneously to understand the main path. Inverting conditions into early exits makes the happy path flat and left-aligned. A method with more than 3 levels of nesting (if inside foreach inside try, etc.) is consistently harder to test and modify safely.

## What It Detects

`ClassMethod` and `Function_` nodes whose nesting depth exceeds `maxDepth`. Nesting is counted by the presence of `if`, `elseif`, `else`, `for`, `foreach`, `while`, `do`, `switch`, `case`, `try`, `catch`, and `match` nodes.

## Transformation

### In `auto` mode

Applies two reduction strategies iteratively until depth is within `maxDepth` or no further reduction is possible:

1. **Guard clause inversion** — when an `if` is the last meaningful statement in its scope, inverts the condition and inserts an early `return` (or `continue` inside a loop). The inverted condition must have no more than `maxNegationComplexity` boolean operators.

2. **Nested `if` merging** — when a single-body `if` contains exactly one child `if` (with no `else`), merges the two conditions with `&&`.

If depth still exceeds `maxDepth` after all passes, adds a TODO comment with the current and maximum depths.

### In `warn` mode

Adds `// TODO: Reduce nesting depth (current: N, max: M)` above the method or function without making structural changes.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `maxDepth` | `int` | `3` | Maximum allowed nesting depth |
| `maxNegationComplexity` | `int` | `2` | Maximum boolean operators in an inverted condition before inversion is skipped |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `'TODO: Reduce nesting depth'` | Comment text prefix used when nesting cannot be reduced |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(ForbidDeepNestingRector::class, [
    'maxDepth' => 3,
    'maxNegationComplexity' => 2,
    'mode' => 'auto',
]);
```

## Example

### Before (guard clause inversion)
```php
class Service
{
    public function process(Order $Order): void
    {
        if ($Order->isValid()) {
            if ($Order->isPaid()) {
                if ($Order->hasItems()) {
                    $this->validate($Order);
                    $this->save($Order);
                    $this->notify($Order);
                }
            }
        }
    }
}
```

### After
```php
class Service
{
    public function process(Order $Order): void
    {
        if (!$Order->isValid()) {
            return;
        }
        if (!$Order->isPaid()) {
            return;
        }
        if (!$Order->hasItems()) {
            return;
        }
        $this->validate($Order);
        $this->save($Order);
        $this->notify($Order);
    }
}
```

### Before (nested if merging)
```php
foreach ($users as $User) {
    if ($User->isActive()) {
        if ($User->hasPermission('edit')) {
            $this->allow($User);
        }
    }
    $this->log($User);
}
```

### After
```php
foreach ($users as $User) {
    if ($User->isActive() && $User->hasPermission('edit')) {
        $this->allow($User);
    }
    $this->log($User);
}
```

### Before (unreducible — fallback to warn)
```php
public function handle(): void
{
    try {
        if ($a) {
            if ($b) {
                // ...
            } else {
                // both branches
            }
        } else {
            // outer else too
        }
    } catch (\Throwable $e) {
        // ...
    }
}
```

### After
```php
// TODO: Reduce nesting depth (current: 4, max: 2)
public function handle(): void
{
    try {
        if ($a) {
            if ($b) {
                // ...
            } else {
                // both branches
            }
        } else {
            // outer else too
        }
    } catch (\Throwable $e) {
        // ...
    }
}
```

## Resolution

When you see the TODO comment:
1. Identify the structural reason depth cannot be reduced automatically (e.g. `if-else` branches, `try-catch` blocks that cannot be inverted).
2. Consider extracting inner blocks into private methods to reset the nesting counter.
3. Eliminate `else` branches by returning/continuing early from the `if` branch.

## Related Rules

- [`ForbidLongClosureRector`](ForbidLongClosureRector.md) — long closures often contribute to nesting problems and should be extracted first
