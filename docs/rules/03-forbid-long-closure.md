# ForbidLongClosureRector

## Tool

Rector (custom rule)

## What it does

Automatically extracts closures that exceed a configurable statement count into named
private methods (when inside a class) or named functions (when at file scope). The
closure is replaced with a first-class callable reference or a short forwarding closure.
When extraction is not safe, adds a TODO comment instead.

## Why it matters

Long closures are the hardest construct for an agent to work with:

1. **Ungrepable** — no name, so an agent cannot search for them by identifier
2. **Hidden state** — captured variables via `use(...)` create invisible dependencies
3. **Untestable** — closures assigned to variables can't be unit tested in isolation
4. **Context-heavy** — understanding requires reading the full surrounding scope

Named functions solve all four problems: searchable, explicit parameters, independently
testable, self-contained.

## Extraction strategy

### Inside a class method

The closure body becomes a new `private` method on the same class. Captured `use`
variables become method parameters. The closure is replaced with a first-class callable
or a short forwarding closure.

```php
// before
class Handler {
    public function boot(): void {
        $render = function (string $message, int $code) use ($Blade): void {
            $html = $Blade->make('error', ['message' => $message])->render();
            (new SapiEmitter())->emit(new HtmlResponse($html, $code));
        };
    }
}

// after
class Handler {
    public function boot(): void {
        $render = fn(string $message, int $code): void =>
            $this->renderError($Blade, $message, $code);
    }

    private function renderError(Blade $Blade, string $message, int $code): void {
        $html = $Blade->make('error', ['message' => $message])->render();
        (new SapiEmitter())->emit(new HtmlResponse($html, $code));
    }
}
```

### At file scope (not inside a class)

The closure body becomes a named function in the same file, placed immediately before
the assignment. Captured `use` variables become function parameters.

```php
// before
$emit_error = static function (string $msg, int $code) use ($Blade): void {
    $html = $Blade->make('error', ['msg' => $msg])->render();
    (new SapiEmitter())->emit(new HtmlResponse($html, $code));
};

// after
function emit_error(Blade $Blade, string $msg, int $code): void {
    $html = $Blade->make('error', ['msg' => $msg])->render();
    (new SapiEmitter())->emit(new HtmlResponse($html, $code));
}
$emit_error = fn(string $msg, int $code): void => emit_error($Blade, $msg, $code);
```

### When extraction is not safe (fall back to TODO)

- The closure captures `$this` by reference in a static context
- The closure captures variables by reference (`use (&$var)`)
- The closure modifies captured variables (write-back through `use`)
- The closure is immediately invoked (IIFE pattern)
- The closure is returned or passed as an argument (not assigned to a variable)

```php
// TODO: Extract closure to a named function or method (captures mutable references)
$transform = function (array &$items) use (&$count): void {
    foreach ($items as &$item) {
        $item = strtoupper($item);
        $count++;
    }
};
```

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `maxStatements` | `int` | `5` | Maximum number of statements before triggering |
| `skipArrowFunctions` | `bool` | `true` | Skip single-expression arrow functions (`fn() =>`) |
| `todoMessage` | `string` | `'TODO: Extract closure to a named function or method'` | Comment when auto-extraction is not safe |

### Example rector.php

```php
use Utils\Rector\Rector\ForbidLongClosureRector;

$rectorConfig->ruleWithConfiguration(ForbidLongClosureRector::class, [
    'maxStatements' => 5,
    'skipArrowFunctions' => true,
]);
```

## Implementation notes

- **Node types**: `Closure`, `ArrowFunction`
- **Statement counting**: Count `$node->stmts` array length. Only count direct children,
  not nested blocks. Arrow functions count as 1 statement (skip by default).
- **Extraction — class context**:
  1. Walk up from the closure node to find the enclosing `Class_` and `ClassMethod`.
  2. Derive method name from the variable name: `$emit_error` → `emitError()`.
  3. Collect `use` variables. For each, resolve the type from the enclosing scope
     (variable assignment, parameter type, etc.). These become method parameters.
  4. Copy the closure's `params`, `returnType`, and `stmts` to the new `ClassMethod`.
  5. Prepend `use` variables as additional params on the new method.
  6. Add the new `ClassMethod` to `$classNode->stmts`.
  7. Replace the original closure with a forwarding closure or callable reference.
- **Extraction — file scope**:
  1. Derive function name from the variable name using snake_case.
  2. Same `use`-to-parameter conversion as class context.
  3. Insert new `Function_` node before the current statement using `array_splice`.
  4. Replace the closure with a forwarding closure that passes the former `use` vars.
- **Safety checks before extraction**:
  - Scan `use` list for by-reference captures (`$useItem->byRef === true`) → abort.
  - Scan closure body for assignments to `use`-captured variables → abort.
  - Check if the closure is the RHS of an `Assign` (extractable) vs. a bare `Arg`
    or `Return_` (not extractable without more context) → abort.
- **Uses `NodeGroup::STMTS_AWARE`** to operate on statement lists for insertion.
- Implements `ConfigurableRectorInterface`
