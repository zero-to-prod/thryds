# SuggestExtractSharedCatchLogicRector

Adds a TODO comment when multiple `catch` blocks in the same `try` statement instantiate the same classes, suggesting extraction of the shared logic.

**Category:** Code Quality
**Mode:** `warn` only
**Auto-fix:** No

## Rationale

When multiple `catch` blocks `new` the same types (e.g. the same emitter and response class), the shared construction logic is duplicated. This is a maintenance hazard: a change to the error-handling pattern must be applied to every block. Shared construction belongs in a private helper method or a factory, leaving each `catch` block to pass only the exception-specific data.

## What It Detects

`TryCatch` nodes with two or more `catch` blocks where at least one class is instantiated (via `new`) in two or more of those blocks.

```php
try {
    run();
} catch (FooException $e) {
    new Emitter()->emit(new Response($e->getMessage()));
} catch (Throwable $e) {
    new Emitter()->emit(new Response('error'));
}
```

`Emitter` and `Response` appear in both catch blocks, so they are considered shared.

## Transformation

### In `warn` mode

Adds a `// TODO: ...` comment above the `try` statement listing the shared class short names. The exact text is configured via `message` with a `%s` placeholder for the comma-separated list.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` disables the rule entirely |
| `message` | `string` | `'TODO: Multiple catch blocks instantiate the same classes (%s) — consider extracting shared logic'` | Comment template; `%s` is replaced with shared class names |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(SuggestExtractSharedCatchLogicRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s). Consider extracting the shared logic.',
]);
```

## Example

### Before
```php
try {
    run();
} catch (FooException $FooException) {
    new Emitter()->emit(new Response($FooException->getMessage()));
} catch (\Throwable $Throwable) {
    new Emitter()->emit(new Response('error'));
}
```

### After
```php
// TODO: Multiple catch blocks instantiate the same classes (Emitter, Response) — consider extracting shared logic
try {
    run();
} catch (FooException $FooException) {
    new Emitter()->emit(new Response($FooException->getMessage()));
} catch (\Throwable $Throwable) {
    new Emitter()->emit(new Response('error'));
}
```

## Resolution

When you see the TODO comment:
1. Identify the shared instantiation pattern across catch blocks (the class names are listed in the comment).
2. Extract the shared construction into a private method that accepts only the varying data (e.g. the message or status code).
3. Replace each catch block's body with a call to the new private method.

Example refactoring:
```php
try {
    run();
} catch (FooException $e) {
    $this->emit($e->getMessage(), 422);
} catch (\Throwable $e) {
    $this->emit('error', 500);
}

private function emit(string $message, int $code): void
{
    new Emitter()->emit(new Response($message, $code));
}
```

## Related Rules

- [`ForbidDeepNestingRector`](ForbidDeepNestingRector.md) — deeply nested try-catch blocks compound the readability problem this rule highlights
