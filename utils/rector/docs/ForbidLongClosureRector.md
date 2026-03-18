# ForbidLongClosureRector

Extracts closures that exceed a statement threshold into named private methods (class context) or named functions (file scope).

**Category:** Code Quality
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

Long closures are anonymous, unnameable, and untestable. They hide logic behind a variable name rather than expressing intent through a function name. When a closure grows beyond a few statements it should become a named method or function so that it can be called, tested, and understood independently. Arrow functions are excluded by default because they are intentionally single-expression.

## What It Detects

A `function(...) { ... }` closure assigned to a variable whose body contains more statements than `maxStatements`. Detected in both class method bodies and namespace-level code.

```php
$render = function (string $message, int $code) use ($Blade): void {
    /* 6 statements */
};
```

If the closure cannot be safely extracted (captures by reference `use (&$var)`, or mutates a captured variable), a TODO comment is added instead even in `auto` mode.

## Transformation

### In `auto` mode

**Class context:** Extracts the closure body into a new `private` method named after the variable (camelCase). Replaces the closure with a forwarding arrow function that delegates to the new method. Captured `use` variables become the first parameters of the new method.

**File/namespace scope:** Extracts the body into a named function (snake_case). Replaces the closure with a forwarding arrow function that calls the new function.

### In `warn` mode

Adds `// TODO: Extract closure to a named function or method` (plus an optional reason suffix) above the closure assignment statement.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `maxStatements` | `int` | `5` | Maximum number of statements before the closure is considered long |
| `skipArrowFunctions` | `bool` | `true` | When `true`, arrow functions are never flagged |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `'TODO: Extract closure to a named function or method'` | Comment text used when extraction is blocked |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(ForbidLongClosureRector::class, [
    'maxStatements' => 5,
    'skipArrowFunctions' => true,
    'mode' => 'auto',
]);
```

## Example

### Before (class context)
```php
class Handler
{
    public function boot(): void
    {
        $render_error = function (string $message, int $code) use ($Blade): void {
            $html = $Blade->make('error', ['message' => $message])->render();
            (new SapiEmitter())->emit(new HtmlResponse($html, $code));
            log_something();
            finish();
        };
    }
}
```

### After
```php
class Handler
{
    public function boot(): void
    {
        $render_error = (fn(string $message, int $code): void => $this->renderError($Blade, $message, $code));
    }
    private function renderError(mixed $Blade, string $message, int $code): void
    {
        $html = $Blade->make('error', ['message' => $message])->render();
        (new SapiEmitter())->emit(new HtmlResponse($html, $code));
        log_something();
        finish();
    }
}
```

### Before (unsafe — captures by reference)
```php
$transform = function (array $items) use (&$count): void {
    foreach ($items as $item) { echo $item; }
    $count++;
    log_it();
    finish();
};
```

### After (warn fallback)
```php
// TODO: Extract closure to a named function or method (captures mutable references)
$transform = function (array $items) use (&$count): void {
    foreach ($items as $item) { echo $item; }
    $count++;
    log_it();
    finish();
};
```

## Resolution

When you see the TODO comment:
1. Identify why extraction was blocked (the reason appears in parentheses: `captures mutable references` or `modifies captured variables`).
2. Refactor the closure to remove by-reference captures or captured-variable mutations.
3. Once safe, Rector will extract it automatically on the next run.

## Related Rules

- [`ForbidDeepNestingRector`](ForbidDeepNestingRector.md) — closures with deep nesting are often a sign the same extraction is needed
