# ForbidDeepNestingRector

## Tool

Rector (custom rule)

## What it does

Automatically reduces nesting depth by inverting conditions into early returns, early
continues, and guard clauses. When the nesting cannot be safely reduced (e.g., the
structure requires both branches), adds a TODO comment instead.

## Why it matters

Each level of nesting adds a condition an agent must hold in memory while reading the
inner code. Flat code with early returns lets an agent read linearly: each line's
meaning is independent of nested context.

## Refactoring strategies (applied in order)

### 1. Invert if-without-else to early return

When a function body is `if (cond) { ...many statements... }` with no `else`, and the
code after the `if` is empty, invert the condition and return early.

```php
// before
function process(Order $Order): void {
    if ($Order->isValid()) {
        $this->validate($Order);
        $this->save($Order);
        $this->notify($Order);
    }
}

// after
function process(Order $Order): void {
    if (!$Order->isValid()) {
        return;
    }
    $this->validate($Order);
    $this->save($Order);
    $this->notify($Order);
}
```

### 2. Invert if-without-else to early continue (inside loops)

Same pattern but inside `foreach`/`for`/`while` — use `continue` instead of `return`.

```php
// before
foreach ($orders as $order) {
    if ($order->isPaid()) {
        foreach ($order->items as $item) {
            if ($item->needsShipping()) {
                $this->ship($item);
            }
        }
    }
}

// after
foreach ($orders as $order) {
    if (!$order->isPaid()) {
        continue;
    }
    foreach ($order->items as $item) {
        if (!$item->needsShipping()) {
            continue;
        }
        $this->ship($item);
    }
}
```

### 3. Merge nested ifs into compound condition

When two `if` statements are directly nested with no other statements, merge them.

```php
// before
if ($user->isActive()) {
    if ($user->hasPermission('edit')) {
        $this->allow();
    }
}

// after
if ($user->isActive() && $user->hasPermission('edit')) {
    $this->allow();
}
```

### When refactoring is not safe (fall back to TODO)

- The `if` has an `else` or `elseif` branch (both paths have logic — cannot trivially
  invert without duplicating the return/continue).
- The `if` wraps code that has statements after it in the same block (early return would
  skip those statements).
- The nesting is caused by `try/catch` (cannot invert exception handling).
- The inversion would require negating a complex expression that is hard to read
  (e.g., `!(($a && $b) || ($c && !$d))`) — use a configurable complexity threshold.

```php
// TODO: Reduce nesting depth (current: 5, max: 3)
function complexHandler(): void {
    try {
        if ($a) {
            if ($b) {
                // ...
            } else {
                // ... both branches have logic
            }
        }
    } catch (Throwable $e) {
        // ...
    }
}
```

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `maxDepth` | `int` | `3` | Maximum nesting depth before triggering |
| `maxNegationComplexity` | `int` | `2` | Max boolean operators in an inverted condition before falling back to TODO |
| `todoMessage` | `string` | `'TODO: Reduce nesting depth'` | Comment prefix when auto-fix is not safe (depth info is appended) |

### Example rector.php

```php
use Utils\Rector\Rector\ForbidDeepNestingRector;

$rectorConfig->ruleWithConfiguration(ForbidDeepNestingRector::class, [
    'maxDepth' => 3,
    'maxNegationComplexity' => 2,
]);
```

## Implementation notes

- **Node types**: `ClassMethod`, `Function_`
- **Depth calculation**: Recursively walk the AST, incrementing for each nesting node:
  `If_`, `ElseIf_`, `Else_`, `For_`, `Foreach_`, `While_`, `Do_`, `Switch_`, `Case_`,
  `TryCatch`, `Catch_`, `Match_`. Track the high-water mark.
- **Refactoring pass**: If depth exceeds `maxDepth`, walk the function body again looking
  for invertable patterns. Apply transformations outermost-first to avoid cascading AST
  changes. Re-measure depth after each transformation. Stop when depth <= `maxDepth` or
  no more safe inversions are available.
- **Condition inversion**: Use `BooleanNot` to wrap the condition. Simplify double
  negation: `!!$x` → `$x`. Simplify comparison inversion: `$a === $b` → `$a !== $b`,
  `$a > $b` → `$a <= $b`. Count boolean operators in the resulting expression —
  if it exceeds `maxNegationComplexity`, abort this inversion.
- **Statement relocation**: When inverting, move the `if` body's statements to the parent
  scope (after the new guard clause). Preserve comments attached to the original `if`.
- **Loop context**: Track whether the current scope is a loop body. Use `continue`
  instead of `return` when inside a loop.
- **Closures/arrow functions**: Treat as separate scopes. Do not count their internal
  nesting toward the parent function. They are analyzed independently.
- Implements `ConfigurableRectorInterface`
